<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\InvalidChat;
use Determly\Chat\Run\Responder;

/**
 * Bound when the AI setting cannot be resolved. The dispatch stream reports the message.
 */
final class ConfigurationResponder implements Responder
{
    public function __construct(private readonly string $message) {}

    public function respond(Turn $turn): iterable
    {
        throw new InvalidChat($this->message);
    }
}
