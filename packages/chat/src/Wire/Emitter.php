<?php

declare(strict_types=1);

namespace Determly\Chat\Wire;

use Determly\Chat\InvalidChat;

/**
 * Speaks the Persona SSE vocabulary from persona-chat.dev.
 * Frame order follows the persona-wire helper shipped with @runtypelabs/persona 4.39.0.
 */
final class Emitter
{
    private int $seq = 0;

    private bool $turnOpen = false;

    private ?string $textBlockId = null;

    private bool $finished = false;

    public function __construct(
        private readonly Writer $writer,
        public readonly string $executionId,
        public readonly string $turnId,
        private readonly string $agentId,
        private readonly string $agentName,
        private readonly string $startedAt,
        private readonly \Closure $id,
        private readonly \Closure $clock,
    ) {}

    public function reasoningStart(string $id): void
    {
        if ($this->finished) {
            return;
        }
        $this->closeTextBlock();
        $this->openTurn();
        $this->send('reasoning_start', ['id' => $id, 'scope' => 'turn']);
    }

    public function reasoningDelta(string $id, string $text): void
    {
        if ($this->finished) {
            return;
        }
        $this->send('reasoning_delta', ['id' => $id, 'delta' => $text]);
    }

    public function reasoningComplete(string $id): void
    {
        if ($this->finished) {
            return;
        }
        $this->send('reasoning_complete', ['id' => $id]);
    }

    public function textDelta(string $text): void
    {
        if ($this->finished) {
            return;
        }
        $this->openTextBlock();
        $this->send('text_delta', ['id' => $this->textBlockId, 'delta' => $text]);
    }

    public function toolStart(string $name, mixed $parameters, string $toolCallId): void
    {
        if ($this->finished) {
            return;
        }
        $this->closeTextBlock();
        $this->openTurn();
        $this->send('tool_start', [
            'toolCallId' => $toolCallId,
            'toolName' => $name,
            'toolType' => 'local',
            'parameters' => $parameters,
            'iteration' => 1,
        ]);
    }

    public function toolComplete(string $toolCallId, mixed $result, bool $success): void
    {
        if ($this->finished) {
            return;
        }
        $this->send('tool_complete', [
            'toolCallId' => $toolCallId,
            'success' => $success,
            'result' => $result,
        ]);
    }

    /**
     * @param  array{toolCallId?: string, toolType?: string, result?: mixed}  $options
     */
    public function toolCall(string $name, mixed $parameters, array $options = []): void
    {
        if ($this->finished) {
            return;
        }
        $this->closeTextBlock();
        $this->openTurn();
        $toolCallId = $options['toolCallId'] ?? ($this->id)('call');
        if (! is_string($toolCallId) || $toolCallId === '') {
            throw new InvalidChat('toolCallId must be a non-empty string.');
        }
        $this->send('tool_start', [
            'toolCallId' => $toolCallId,
            'toolName' => $name,
            'toolType' => $options['toolType'] ?? 'local',
            'parameters' => $parameters,
            'iteration' => 1,
        ]);
        $this->send('tool_complete', [
            'toolCallId' => $toolCallId,
            'success' => true,
            'result' => $options['result'] ?? new \stdClass,
        ]);
    }

    /**
     * @param  list<string|array{label: string, prompt?: string, description?: string}>  $items
     */
    public function suggestReplies(array $items): void
    {
        $this->toolCall('suggest_replies', ['suggestions' => $items], [
            'result' => ['content' => [['type' => 'text', 'text' => 'Suggestions shown to the user.']]],
        ]);
    }

    public function complete(): void
    {
        if ($this->finished) {
            return;
        }
        $this->finished = true;
        $this->closeTextBlock();
        if ($this->turnOpen) {
            $this->send('turn_complete', [
                'id' => $this->turnId,
                'role' => 'assistant',
                'iteration' => 1,
                'stopReason' => 'end_turn',
                'completedAt' => ($this->clock)(),
            ]);
            $this->turnOpen = false;
        }
        $this->send('execution_complete', [
            'kind' => 'agent',
            'success' => true,
            'completedAt' => ($this->clock)(),
        ]);
    }

    public function error(string $message): void
    {
        if ($this->finished) {
            return;
        }
        $this->finished = true;
        $this->send('execution_error', [
            'kind' => 'agent',
            'error' => ['code' => 'adapter_error', 'message' => $message],
        ]);
    }

    /**
     * Pause the turn. The widget resumes with POST {apiUrl}/resume or the approval URL.
     * No execution_complete frame is sent.
     *
     * @param  array<string, mixed>  $fields
     */
    public function pause(string $reason, array $fields): void
    {
        if ($this->finished) {
            return;
        }
        $this->closeTextBlock();
        $this->finished = true;
        $this->send('step_await', ['awaitReason' => $reason, 'executionId' => $this->executionId] + $fields);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function approvalStart(string $approvalId, string $toolName, string $description, array $parameters): void
    {
        if ($this->finished) {
            return;
        }
        $this->closeTextBlock();
        $this->openTurn();
        $this->send('agent_approval_start', [
            'approvalId' => $approvalId,
            'toolName' => $toolName,
            'toolType' => 'local',
            'description' => $description,
            'parameters' => $parameters,
            'executionId' => $this->executionId,
        ]);
    }

    public function approvalComplete(string $approvalId, string $decision): void
    {
        if ($this->finished) {
            return;
        }
        $this->send('agent_approval_complete', [
            'approvalId' => $approvalId,
            'decision' => $decision,
            'executionId' => $this->executionId,
        ]);
    }

    public function open(): void
    {
        $this->send('execution_start', [
            'kind' => 'agent',
            'agentId' => $this->agentId,
            'agentName' => $this->agentName,
            'startedAt' => $this->startedAt,
        ]);
    }

    private function openTurn(): void
    {
        if ($this->turnOpen) {
            return;
        }
        $this->send('turn_start', [
            'id' => $this->turnId,
            'role' => 'assistant',
            'iteration' => 1,
        ]);
        $this->turnOpen = true;
    }

    private function openTextBlock(): void
    {
        $this->openTurn();
        if ($this->textBlockId !== null) {
            return;
        }
        $this->textBlockId = ($this->id)('text');
        $this->send('text_start', ['id' => $this->textBlockId]);
    }

    private function closeTextBlock(): void
    {
        if ($this->textBlockId === null) {
            return;
        }
        $this->send('text_complete', ['id' => $this->textBlockId]);
        $this->textBlockId = null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $event, array $payload): void
    {
        $body = ['type' => $event, 'executionId' => $this->executionId, 'seq' => $this->seq] + $payload;
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->seq++;
        $this->writer->write("event: {$event}\ndata: {$json}\n\n");
    }
}
