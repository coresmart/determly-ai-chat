<?php

declare(strict_types=1);

namespace Determly\Chat\Tests;

use Determly\Chat\Dispatch\Messages;
use Determly\Chat\Dispatch\Turn;
use Determly\Chat\EchoAgent;
use Determly\Chat\InvalidChat;
use Determly\Chat\Tool;
use Determly\Chat\ToolRegistry;
use Determly\Chat\Wire\BufferWriter;
use Determly\Chat\Wire\Emitter;
use Determly\Chat\Wire\Frame;
use Determly\Chat\Wire\Session;
use PHPUnit\Framework\TestCase;

final class PersonaWireTest extends TestCase
{
    public function test_text_turn_matches_persona_frame_order(): void
    {
        $frames = $this->events(function (Emitter $emit): void {
            $emit->textDelta('Hello');
            $emit->complete();
        });

        $this->assertSame([
            'execution_start',
            'turn_start',
            'text_start',
            'text_delta',
            'text_complete',
            'turn_complete',
            'execution_complete',
        ], array_map(fn (Frame $frame): string => $frame->event, $frames));

        $this->assertSame('exec_1', $frames[0]->data['executionId']);
        $this->assertSame(0, $frames[0]->data['seq']);
        $this->assertSame('agent', $frames[0]->data['kind']);
        $this->assertSame('Hello', $frames[3]->data['delta']);
        $this->assertSame('end_turn', $frames[5]->data['stopReason']);
        $this->assertSame(6, $frames[6]->data['seq']);
        $this->assertTrue($frames[6]->data['success']);
    }

    public function test_tool_call_closes_the_open_text_block_first(): void
    {
        $frames = $this->events(function (Emitter $emit): void {
            $emit->textDelta('Looking.');
            $emit->toolCall('tinyfish_search', ['query' => 'laravel'], [
                'toolCallId' => 'call_1',
                'result' => ['free' => true],
            ]);
            $emit->textDelta('Done.');
            $emit->complete();
        });

        $this->assertSame([
            'execution_start',
            'turn_start',
            'text_start',
            'text_delta',
            'text_complete',
            'tool_start',
            'tool_complete',
            'text_start',
            'text_delta',
            'text_complete',
            'turn_complete',
            'execution_complete',
        ], array_map(fn (Frame $frame): string => $frame->event, $frames));

        $tool = $frames[5]->data;
        $this->assertSame('tinyfish_search', $tool['toolName']);
        $this->assertSame('local', $tool['toolType']);
        $this->assertSame(['query' => 'laravel'], $tool['parameters']);
        $this->assertArrayNotHasKey('origin', $tool);
        $this->assertSame(['free' => true], $frames[6]->data['result']);
    }

    public function test_suggest_replies_uses_the_builtin_tool(): void
    {
        $frames = $this->events(function (Emitter $emit): void {
            $emit->textDelta('Pick one.');
            $emit->suggestReplies(['Search', 'Fetch']);
            $emit->complete();
        });

        $start = $this->event($frames, 'tool_start');
        $this->assertSame('suggest_replies', $start->data['toolName']);
        $this->assertSame(['suggestions' => ['Search', 'Fetch']], $start->data['parameters']);
    }

    public function test_a_thrown_handler_emits_execution_error(): void
    {
        $writer = new BufferWriter;
        Session::run($writer, function (): void {
            throw new \RuntimeException('wallet empty');
        }, id: $this->ids(), clock: $this->clock());

        $frames = Frame::parse($writer->body);
        $this->assertSame(['execution_start', 'execution_error'], array_map(fn (Frame $frame): string => $frame->event, $frames));
        $this->assertSame('wallet empty', $frames[1]->data['error']['message']);
        $this->assertSame('adapter_error', $frames[1]->data['error']['code']);
    }

    public function test_dispatch_body_flattens_persona_messages(): void
    {
        $turn = Turn::fromArray([
            'messages' => [
                ['role' => 'system', 'content' => 'Be brief.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => 'Search '],
                    ['type' => 'text', 'text' => 'Laravel'],
                ]],
                ['role' => 'tool', 'content' => 'ignored'],
            ],
            'context' => ['page' => 'docs'],
            'metadata' => [],
        ]);

        $this->assertSame([
            ['role' => 'system', 'content' => 'Be brief.'],
            ['role' => 'user', 'content' => 'Search Laravel'],
        ], $turn->messages);
        $this->assertSame(['page' => 'docs'], $turn->context);
    }

    public function test_echo_agent_repeats_the_latest_user_message(): void
    {
        $writer = new BufferWriter;
        $turn = Turn::fromArray([
            'messages' => [
                ['role' => 'user', 'content' => 'First'],
                ['role' => 'assistant', 'content' => 'Ok'],
                ['role' => 'user', 'content' => 'Second'],
            ],
        ]);

        Session::run($writer, fn (Emitter $emit) => (new EchoAgent)->respond($turn, $emit), id: $this->ids(), clock: $this->clock());
        $frames = Frame::parse($writer->body);
        $deltas = array_values(array_filter($frames, fn (Frame $frame): bool => $frame->event === 'text_delta'));

        $this->assertSame('Second', $deltas[0]->data['delta']);
    }

    public function test_tool_names_are_unique_across_packages(): void
    {
        $registry = new ToolRegistry;
        $registry->register(new NamedTool('tinyfish_search'), 'determly/recipe-tinyfish-base');

        $this->expectException(InvalidChat::class);
        $this->expectExceptionMessage('already registered by [determly/recipe-tinyfish-base]');
        $registry->register(new NamedTool('tinyfish_search'), 'determly/recipe-other');
    }

    public function test_non_object_context_is_rejected(): void
    {
        $this->expectException(InvalidChat::class);
        Turn::fromArray(['context' => ['not', 'an object']]);
    }

    /**
     * @param  callable(Emitter): void  $handler
     * @return list<Frame>
     */
    private function events(callable $handler): array
    {
        $writer = new BufferWriter;
        Session::run($writer, $handler, 'determly', 'Assistant', $this->ids(), $this->clock());

        return Frame::parse($writer->body);
    }

    /**
     * @param  list<Frame>  $frames
     */
    private function event(array $frames, string $name): Frame
    {
        foreach ($frames as $frame) {
            if ($frame->event === $name) {
                return $frame;
            }
        }
        $this->fail('Missing event '.$name);
    }

    private function ids(): \Closure
    {
        return function (string $prefix): string {
            static $n = 0;
            if ($prefix === 'exec') {
                return 'exec_1';
            }
            if ($prefix === 'turn') {
                return 'turn_1';
            }
            $n++;

            return $prefix.'_'.$n;
        };
    }

    private function clock(): \Closure
    {
        return fn (): string => '2026-11-16T17:00:00.000Z';
    }
}

final class NamedTool implements Tool
{
    public function __construct(private readonly string $toolName) {}

    public function name(): string
    {
        return $this->toolName;
    }

    public function description(): string
    {
        return 'Search';
    }

    public function parameters(): array
    {
        return ['type' => 'object'];
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function handle(array $parameters): mixed
    {
        return ['ok' => true];
    }
}
