<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Tests;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Gallery\ExampleSlug;
use Determly\Chat\Gallery\GalleryResponder;
use Determly\Chat\Gallery\GalleryWidget;
use Determly\Chat\Gallery\Tools\QueryOrders;
use Determly\Chat\Gallery\Tools\RefundOrder;
use Determly\Chat\Run\MemoryExecutionStore;
use Determly\Chat\Run\Runner;
use Determly\Chat\ToolRegistry;
use Determly\Chat\Wire\BufferWriter;
use Determly\Chat\Wire\Emitter;
use Determly\Chat\Wire\Frame;
use Determly\Chat\Wire\Session;
use PHPUnit\Framework\TestCase;

final class GalleryResponderTest extends TestCase
{
    public function test_data_runs_the_query_then_streams_a_markdown_artifact(): void
    {
        $tools = new ToolRegistry;
        $tools->register(new QueryOrders, 'gallery');

        $frames = $this->play('data', [
            'messages' => [['role' => 'user', 'content' => 'Show the orders']],
        ], $tools);

        $this->assertSame('query_orders', $this->event($frames, 'tool_start')->data['toolName']);
        $this->assertTrue($this->event($frames, 'tool_complete')->data['success']);
        $this->assertArrayHasKey('rows', $this->event($frames, 'tool_complete')->data['result']);
        $start = $this->event($frames, 'artifact_start');
        $this->assertSame('markdown', $start->data['artifactType']);
        $this->assertSame('Orders', $start->data['title']);
        $this->assertStringContainsString('| 1001 |', $this->event($frames, 'artifact_delta')->data['delta']);
        $this->assertSame('execution_complete', $this->names($frames)[array_key_last($this->names($frames))]);
    }

    public function test_approval_pauses_and_handle_runs_only_after_approval(): void
    {
        $tools = new ToolRegistry;
        $tool = new RefundOrder;
        $tools->register($tool, 'gallery');
        $store = new MemoryExecutionStore;

        $paused = $this->play('approval', [
            'messages' => [['role' => 'user', 'content' => 'Refund order 1001']],
        ], $tools, $store);

        $this->assertContains('approval_start', $this->names($paused));
        $await = $this->event($paused, 'await');
        $this->assertSame('approval_required', $await->data['awaitReason']);
        $this->assertSame('refund_order', $await->data['toolName']);
        $this->assertNotContains('execution_complete', $this->names($paused));
        $this->assertNotContains('tool_complete', $this->names($paused));
        $this->assertSame(0, $tool->runs);

        $continued = $this->play('approval', [], $tools, $store, (string) $await->data['executionId'], function (Runner $runner, Emitter $emit) use ($await): void {
            $runner->decide((string) $await->data['executionId'], (string) $await->data['approvalId'], 'approved', $emit);
        });

        $this->assertSame(1, $tool->runs);
        $this->assertTrue($this->event($continued, 'tool_complete')->data['success']);
        $this->assertTrue($this->event($continued, 'tool_complete')->data['result']['refunded']);
        $this->assertStringContainsString('recorded', $this->text($continued));
    }

    public function test_ask_user_question_pauses_for_the_client_tool(): void
    {
        $store = new MemoryExecutionStore;
        $paused = $this->play('ask', [
            'messages' => [['role' => 'user', 'content' => 'Ask me which orders to focus on']],
            'clientTools' => [[
                'name' => 'ask_user_question',
                'origin' => 'webmcp',
            ]],
        ], store: $store);

        $await = $this->event($paused, 'await');
        $this->assertSame('local_tool_required', $await->data['awaitReason']);
        $this->assertSame('ask_user_question', $await->data['toolName']);
        $this->assertSame('webmcp', $await->data['origin']);
        $this->assertNotContains('execution_complete', $this->names($paused));

        $continued = $this->play('ask', [], store: $store, executionId: (string) $await->data['executionId'], handler: function (Runner $runner, Emitter $emit) use ($await): void {
            $runner->resume((string) $await->data['executionId'], [
                (string) $await->data['toolCallId'] => [
                    'content' => [['type' => 'text', 'text' => 'Open']],
                ],
            ], $emit);
        });

        $this->assertContains('suggest_replies', $this->toolNames($continued));
        $this->assertStringContainsString('Thanks', $this->text($continued));
    }

    public function test_add_to_cart_pauses_so_the_page_can_run_it(): void
    {
        $paused = $this->play('actions', [
            'messages' => [['role' => 'user', 'content' => 'Add the trail shoe to the cart']],
            'clientTools' => [[
                'name' => 'add_to_cart',
                'origin' => 'webmcp',
            ]],
        ]);

        $await = $this->event($paused, 'await');
        $this->assertSame('local_tool_required', $await->data['awaitReason']);
        $this->assertSame('add_to_cart', $await->data['toolName']);
        $this->assertSame('webmcp', $await->data['origin']);
        $this->assertSame('SHOE-014', $await->data['parameters']['sku']);
        $this->assertSame('Trail shoe', $await->data['parameters']['title']);
        $this->assertNotContains('execution_complete', $this->names($paused));
        $this->assertNotContains('tool_complete', $this->names($paused));
    }

    public function test_component_reply_is_only_json(): void
    {
        $frames = $this->play('components', [
            'messages' => [['role' => 'user', 'content' => 'Show a contact form']],
        ]);

        $text = trim($this->text($frames));
        $decoded = json_decode($text, true);
        $this->assertIsArray($decoded);
        $this->assertSame('DynamicForm', $decoded['component']);
        $this->assertSame('Tell me how to reach you.', $decoded['text']);
        $this->assertSame('email', $decoded['props']['fields'][0]['type']);
        $this->assertSame('execution_complete', $this->names($frames)[array_key_last($this->names($frames))]);
    }

    public function test_widget_api_url_has_no_query_string(): void
    {
        $html = GalleryWidget::html('data');

        $this->assertStringContainsString('/examples/data/dispatch', $html);
        $this->assertStringNotContainsString('/examples/data/dispatch?', $html);
        $this->assertStringContainsString('Show the orders', $html);
        $this->assertStringContainsString('autoExpand = true', $html);
        $this->assertStringNotContainsString('autoFocusInput', $html);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  callable(Runner, Emitter): void|null  $handler
     * @return list<Frame>
     */
    private function play(
        string $slug,
        array $body,
        ?ToolRegistry $tools = null,
        ?MemoryExecutionStore $store = null,
        ?string $executionId = null,
        ?callable $handler = null,
    ): array {
        $writer = new BufferWriter;
        $runner = new Runner(new GalleryResponder(new ExampleSlug($slug)), $tools ?? new ToolRegistry, $store ?? new MemoryExecutionStore);
        Session::run($writer, function (Emitter $emit) use ($runner, $body, $handler): void {
            if ($handler !== null) {
                $handler($runner, $emit);

                return;
            }
            $runner->dispatch(Turn::fromArray($body), $emit);
        }, executionId: $executionId);

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

    /**
     * @param  list<Frame>  $frames
     */
    private function text(array $frames): string
    {
        $text = '';
        foreach ($frames as $frame) {
            if ($frame->event === 'text_delta') {
                $text .= $frame->data['delta'];
            }
        }

        return $text;
    }
}
