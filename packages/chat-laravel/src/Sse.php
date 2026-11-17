<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Wire\Emitter;
use Determly\Chat\Wire\Session;
use Determly\Chat\Wire\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Sse
{
    /**
     * @param  callable(Emitter): void  $handler
     */
    public static function stream(callable $handler, ?string $executionId = null): StreamedResponse
    {
        $agentId = (string) config('determly-chat.agent_id', 'determly');
        $agentName = (string) config('determly-chat.agent_name', 'Assistant');

        return response()->stream(function () use ($handler, $agentId, $agentName, $executionId): void {
            Session::run(new class implements Writer
            {
                public function write(string $chunk): void
                {
                    echo $chunk;
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }, $handler, $agentId, $agentName, executionId: $executionId);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
