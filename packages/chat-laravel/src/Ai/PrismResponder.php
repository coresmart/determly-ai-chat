<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\InvalidChat;
use Determly\Chat\Run\Responder;
use Determly\Chat\Run\Step;
use Determly\Chat\ToolRegistry;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Streaming\Events\ErrorEvent;
use Prism\Prism\Streaming\Events\TextDeltaEvent;
use Prism\Prism\Streaming\Events\ThinkingEvent;
use Prism\Prism\Streaming\Events\ToolCallEvent;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use RuntimeException;

/**
 * Streams one Prism step into Persona steps.
 * withMaxSteps(1) stops Prism from sending the empty tool callback back to the model.
 * The callback returns an empty string because the Determly runner executes the tool.
 */
final class PrismResponder implements Responder
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly string $instructions,
        private readonly ?string $provider,
        private readonly ?string $model,
    ) {}

    public function respond(Turn $turn): iterable
    {
        if ($this->provider === null || $this->model === null) {
            throw new InvalidChat('Prism needs DETERMLY_CHAT_PROVIDER and DETERMLY_CHAT_MODEL.');
        }

        $split = Transcript::split($this->instructions, $turn->messages);
        $messages = [];
        foreach ($split['history'] as $message) {
            $messages[] = $message['role'] === 'assistant'
                ? new AssistantMessage($message['content'])
                : new UserMessage($message['content']);
        }
        $messages[] = new UserMessage($split['prompt']);

        $sdkTools = [];
        foreach (ToolCatalog::forTurn($this->tools, $turn) as $tool) {
            $sdkTools[] = ToolParameters::prism($tool);
        }

        $stream = Prism::text()
            ->using($this->provider, $this->model)
            ->withSystemPrompt($split['instructions'])
            ->withMessages($messages)
            ->withTools($sdkTools)
            ->withMaxSteps(1)
            ->asStream();

        foreach ($stream as $event) {
            if ($event instanceof TextDeltaEvent && $event->delta !== '') {
                yield Step::text($event->delta);

                continue;
            }
            if ($event instanceof ThinkingEvent && $event->delta !== '') {
                yield Step::reasoning($event->delta);

                continue;
            }
            if ($event instanceof ToolCallEvent) {
                yield Step::tool($event->toolCall->name, $event->toolCall->arguments());

                continue;
            }
            if ($event instanceof ErrorEvent && ! $event->recoverable) {
                throw new RuntimeException($event->message);
            }
        }
    }
}
