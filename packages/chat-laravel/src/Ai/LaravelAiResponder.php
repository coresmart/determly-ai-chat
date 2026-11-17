<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Run\Responder;
use Determly\Chat\Run\Step;
use Determly\Chat\ToolRegistry;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use RuntimeException;

/**
 * Streams one Laravel AI step into Persona steps.
 * Tool calls are proposals. The runner executes them and calls this responder again.
 */
final class LaravelAiResponder implements Responder
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly string $instructions,
        private readonly ?string $provider,
        private readonly ?string $model,
    ) {}

    public function respond(Turn $turn): iterable
    {
        $split = Transcript::split($this->instructions, $turn->messages);
        $history = [];
        foreach ($split['history'] as $message) {
            $history[] = $message['role'] === 'assistant'
                ? new AssistantMessage($message['content'])
                : new UserMessage($message['content']);
        }
        $sdkTools = [];
        foreach (ToolCatalog::forTurn($this->tools, $turn) as $tool) {
            $sdkTools[] = new LaravelTool($tool);
        }

        $agent = new LaravelChatAgent($split['instructions'], $history, $sdkTools);
        $stream = $agent->stream($split['prompt'], provider: $this->provider, model: $this->model);

        foreach ($stream as $event) {
            if ($event instanceof TextDelta && $event->delta !== '') {
                yield Step::text($event->delta);

                continue;
            }
            if ($event instanceof ReasoningDelta && $event->delta !== '') {
                yield Step::reasoning($event->delta);

                continue;
            }
            if ($event instanceof ToolCall) {
                $arguments = $event->toolCall->arguments;

                yield Step::tool($event->toolCall->name, is_array($arguments) ? $arguments : []);

                continue;
            }
            if ($event instanceof Error && ! $event->recoverable) {
                throw new RuntimeException($event->message);
            }
        }
    }
}
