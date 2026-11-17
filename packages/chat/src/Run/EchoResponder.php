<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

use Determly\Chat\Dispatch\Turn;

/**
 * Streams the latest user message back, the same shape as Persona's echo adapter.
 * Bind another Responder when a real model is available.
 */
final class EchoResponder implements Responder
{
    public function respond(Turn $turn): iterable
    {
        $last = '';
        foreach ($turn->messages as $message) {
            if ($message['role'] === 'user' && $message['content'] !== '') {
                $last = $message['content'];
            }
        }

        $reply = $last === ''
            ? 'Send a message and the echo agent will stream it back.'
            : 'You said: "'.$last.'". This is the echo agent. Bind a Responder to use a real model.';

        foreach (preg_split('/\s+/', trim($reply)) ?: [] as $word) {
            if ($word === '') {
                continue;
            }
            yield Step::text($word.' ');
        }

        yield Step::suggestions([
            ['label' => 'Say that again'],
            ['label' => 'What tools are available?', 'prompt' => 'What tools are available?'],
        ]);
    }
}
