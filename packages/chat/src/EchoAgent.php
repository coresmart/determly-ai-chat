<?php

declare(strict_types=1);

namespace Determly\Chat;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Wire\Emitter;

/**
 * Safe default so the dispatch route answers before an AI library is bound.
 * Repeats the latest user message. Recipes replace this binding.
 */
final class EchoAgent implements Agent
{
    public function respond(Turn $turn, Emitter $emit): void
    {
        $last = 'Hello.';
        foreach ($turn->messages as $message) {
            if ($message['role'] === 'user' && $message['content'] !== '') {
                $last = $message['content'];
            }
        }
        $emit->textDelta($last);
        $emit->complete();
    }
}
