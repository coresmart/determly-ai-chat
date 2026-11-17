<?php

declare(strict_types=1);

namespace Determly\Chat\Tests;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Run\EchoResponder;
use Determly\Chat\Run\ExecutionStore;
use Determly\Chat\Run\MemoryExecutionStore;
use Determly\Chat\Run\Responder;
use Determly\Chat\Run\Runner;
use Determly\Chat\Run\Step;
use Determly\Chat\Tool;
use Determly\Chat\ToolRegistry;
use Determly\Chat\Wire\BufferWriter;
use Determly\Chat\Wire\Emitter;
use Determly\Chat\Wire\Frame;
use Determly\Chat\Wire\Session;
use PHPUnit\Framework\TestCase;

final class RunnerTest extends TestCase
{
    public function test_echo_streams_words_and_suggestions_then_finishes(): void
    {
        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'Hello there']],
            ]), $emit);
        }, new EchoResponder);

        $events = $this->names($frames);
        $this->assertContains('text_delta', $events);
        $this->assertContains('suggest_replies', $this->toolNames($frames));
        $this->assertSame('execution_complete', $events[array_key_last($events)]);
        $text = '';
        foreach ($frames as $frame) {
            if ($frame->event === 'text_delta') {
                $text .= $frame->data['delta'];
            }
        }
        $this->assertStringContainsString('You said: "Hello there".', $text);
        $this->assertGreaterThan(3, count(array_filter($frames, fn (Frame $frame): bool => $frame->event === 'text_delta')));
    }

    public function test_server_tool_runs_and_the_responder_continues(): void
    {
        $tools = new ToolRegistry;
        $tools->register(new SearchTool, 'determly/recipe-tinyfish-base');
        $responder = new ScriptResponder([
            [Step::tool('tinyfish_search', ['query' => 'laravel'])],
            [Step::text('Found it.')],
        ]);

        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'search']],
            ]), $emit);
        }, $responder, $tools);

        $this->assertSame('tinyfish_search', $this->event($frames, 'tool_start')->data['toolName']);
        $this->assertTrue($this->event($frames, 'tool_complete')->data['success']);
        $this->assertSame(['free' => true, 'query' => 'laravel'], $this->event($frames, 'tool_complete')->data['result']);
        $this->assertSame('Found it.', $this->event($frames, 'text_delta')->data['delta']);
        $this->assertSame('execution_complete', $this->names($frames)[array_key_last($this->names($frames))]);
        $this->assertSame(2, $responder->calls);
    }

    public function test_approval_pauses_and_resume_is_denied_without_running_the_tool(): void
    {
        $tools = new ToolRegistry;
        $tool = new SearchTool(true);
        $tools->register($tool, 'determly/recipe-tinyfish-base');
        $store = new MemoryExecutionStore;
        $responder = new ScriptResponder([
            [Step::tool('tinyfish_search', ['query' => 'billing'])],
            [Step::text('Stopped.')],
        ]);

        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'search']],
            ]), $emit);
        }, $responder, $tools, $store);

        $names = $this->names($frames);
        $this->assertContains('approval_start', $names);
        $this->assertContains('await', $names);
        $this->assertNotContains('execution_complete', $names);
        $this->assertSame('approval_required', $this->event($frames, 'await')->data['awaitReason']);
        $this->assertSame(0, $tool->runs);

        $executionId = $frames[0]->data['executionId'];
        $approvalId = $this->event($frames, 'await')->data['approvalId'];
        $continued = $this->play(function (Runner $runner, Emitter $emit) use ($executionId, $approvalId): void {
            $runner->decide($executionId, $approvalId, 'denied', $emit);
        }, $responder, $tools, $store, $executionId);

        $this->assertNotContains('tool_complete', $this->names($continued));
        $this->assertSame('Stopped.', $this->event($continued, 'text_delta')->data['delta']);
        $this->assertSame('denied', $this->event($continued, 'approval_complete')->data['decision']);
    }

    public function test_approved_tool_runs_once_and_can_be_remembered(): void
    {
        $tools = new ToolRegistry;
        $tool = new SearchTool(true);
        $tools->register($tool, 'determly/recipe-tinyfish-base');
        $store = new MemoryExecutionStore;
        $responder = new ScriptResponder([
            [Step::tool('tinyfish_search', ['query' => 'billing'])],
            [Step::text('Done.')],
            [Step::tool('tinyfish_search', ['query' => 'again'])],
            [Step::text('Again.')],
        ]);

        $paused = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'search']],
            ]), $emit);
        }, $responder, $tools, $store);
        $executionId = $paused[0]->data['executionId'];
        $approvalId = $this->event($paused, 'await')->data['approvalId'];

        $continued = $this->play(function (Runner $runner, Emitter $emit) use ($executionId, $approvalId): void {
            $runner->decide($executionId, $approvalId, 'approved', $emit, true);
        }, $responder, $tools, $store, $executionId);

        $this->assertSame(1, $tool->runs);
        $this->assertTrue($store->allowed('tinyfish_search'));
        $this->assertSame('Done.', $this->event($continued, 'text_delta')->data['delta']);
    }

    public function test_client_tool_pauses_until_resume_posts_the_output(): void
    {
        $store = new MemoryExecutionStore;
        $responder = new ScriptResponder([
            [Step::tool('webmcp:cart.add', ['sku' => 'a'])],
            [Step::text('Added.')],
        ]);

        $paused = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'add it']],
                'clientTools' => [['name' => 'webmcp:cart.add']],
            ]), $emit);
        }, $responder, store: $store);

        $await = $this->event($paused, 'await');
        $this->assertSame('local_tool_required', $await->data['awaitReason']);
        $this->assertSame('webmcp:cart.add', $await->data['toolName']);
        $this->assertNotContains('execution_complete', $this->names($paused));

        $continued = $this->play(function (Runner $runner, Emitter $emit) use ($await): void {
            $runner->resume((string) $await->data['executionId'], [
                (string) $await->data['toolCallId'] => [
                    'content' => [['type' => 'text', 'text' => 'sku a added']],
                ],
            ], $emit);
        }, $responder, store: $store, executionId: (string) $await->data['executionId']);

        $this->assertSame('Added.', $this->event($continued, 'text_delta')->data['delta']);
        $this->assertSame(2, $responder->calls);
    }

    public function test_a_webmcp_page_tool_pauses_with_its_origin(): void
    {
        $responder = new ScriptResponder([
            [Step::tool('refresh_orders', [])],
        ]);

        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'refresh']],
                'clientTools' => [[
                    'name' => 'refresh_orders',
                    'origin' => 'webmcp',
                    'description' => 'Reload the orders table.',
                    'parametersSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'order_id' => ['type' => 'string'],
                        ],
                    ],
                ]],
            ]), $emit);
        }, $responder);

        $await = $this->event($frames, 'await');
        $this->assertSame('local_tool_required', $await->data['awaitReason']);
        $this->assertSame('refresh_orders', $await->data['toolName']);
        $this->assertSame('webmcp', $await->data['origin']);
        $this->assertNotContains('execution_complete', $this->names($frames));
    }

    public function test_artifact_tools_stream_persona_artifact_frames(): void
    {
        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'show the note']],
            ]), $emit);
        }, new ScriptResponder([
            [Step::tool('emit_artifact_markdown', ['title' => 'Note', 'content' => 'Hello'])],
            [Step::text('Shown.')],
        ]));

        $start = $this->event($frames, 'artifact_start');
        $this->assertSame('markdown', $start->data['artifactType']);
        $this->assertSame('Note', $start->data['title']);
        $this->assertSame('Hello', $this->event($frames, 'artifact_delta')->data['delta']);
        $this->assertSame($start->data['id'], $this->event($frames, 'artifact_complete')->data['id']);
        $this->assertNotContains('tool_start', $this->names($frames));
        $this->assertSame('Shown.', $this->event($frames, 'text_delta')->data['delta']);

        $component = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'show the card']],
            ]), $emit);
        }, new ScriptResponder([
            [Step::tool('emit_artifact_component', [
                'title' => 'Card',
                'component' => 'ProductCard',
                'props' => '{"title":"Trail shoe","price":"$129"}',
            ])],
        ]));

        $panel = $this->event($component, 'artifact_start');
        $this->assertSame('component', $panel->data['artifactType']);
        $this->assertSame('ProductCard', $panel->data['component']);
        $this->assertSame('Trail shoe', $this->event($component, 'artifact_update')->data['props']['title']);
    }

    public function test_reasoning_closes_before_the_answer(): void
    {
        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => 'think']],
            ]), $emit);
        }, new ScriptResponder([
            [Step::reasoning('Checking'), Step::text('Ready.')],
        ]));

        $names = $this->names($frames);
        $this->assertLessThan(
            array_search('text_delta', $names, true),
            array_search('reasoning_complete', $names, true),
        );
        $this->assertSame('Checking', $this->event($frames, 'reasoning_delta')->data['delta']);
        $this->assertSame('Ready.', $this->event($frames, 'text_delta')->data['delta']);
    }

    public function test_resume_without_a_paused_execution_is_an_error(): void
    {
        $frames = $this->play(function (Runner $runner, Emitter $emit): void {
            $runner->resume('exec_missing', [], $emit);
        }, new EchoResponder);

        $this->assertSame('execution_error', $this->names($frames)[array_key_last($this->names($frames))]);
    }

    /**
     * @param  callable(Runner, Emitter): void  $handler
     * @return list<Frame>
     */
    private function play(
        callable $handler,
        Responder $responder,
        ?ToolRegistry $tools = null,
        ?ExecutionStore $store = null,
        ?string $executionId = null,
    ): array {
        $writer = new BufferWriter;
        $runner = new Runner($responder, $tools ?? new ToolRegistry, $store ?? new MemoryExecutionStore);
        Session::run($writer, fn (Emitter $emit) => $handler($runner, $emit), executionId: $executionId);

        return Frame::parse($writer->body);
    }

    /**
     * @param  list<Frame>  $frames
     * @return list<string>
     */
    private function names(array $frames): array
    {
        return array_map(fn (Frame $frame): string => $frame->event, $frames);
    }

    /**
     * @param  list<Frame>  $frames
     * @return list<string>
     */
    private function toolNames(array $frames): array
    {
        $names = [];
        foreach ($frames as $frame) {
            if ($frame->event === 'tool_start' && is_string($frame->data['toolName'] ?? null)) {
                $names[] = $frame->data['toolName'];
            }
        }

        return $names;
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
}

final class SearchTool implements Tool
{
    public int $runs = 0;

    public function __construct(private readonly bool $approval = false) {}

    public function name(): string
    {
        return 'tinyfish_search';
    }

    public function description(): string
    {
        return 'Search the web. Search does not spend credits.';
    }

    public function parameters(): array
    {
        return ['type' => 'object'];
    }

    public function requiresApproval(): bool
    {
        return $this->approval;
    }

    public function handle(array $parameters): mixed
    {
        $this->runs++;

        return ['free' => true, 'query' => $parameters['query'] ?? null];
    }
}

final class ScriptResponder implements Responder
{
    public int $calls = 0;

    /**
     * @param  list<list<Step>>  $rounds
     */
    public function __construct(private array $rounds) {}

    public function respond(Turn $turn): iterable
    {
        $steps = $this->rounds[$this->calls] ?? [];
        $this->calls++;
        yield from $steps;
    }
}
