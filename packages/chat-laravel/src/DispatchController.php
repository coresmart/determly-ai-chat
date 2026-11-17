<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Agent;
use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Wire\Emitter;
use Determly\Chat\Wire\Session;
use Determly\Chat\Wire\Writer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DispatchController
{
    public function __invoke(Request $request, Agent $agent): StreamedResponse
    {
        $body = $request->json()->all();
        $turn = Turn::fromArray(is_array($body) ? $body : []);
        $agentId = (string) config('determly-chat.agent_id', 'determly');
        $agentName = (string) config('determly-chat.agent_name', 'Assistant');

        return response()->stream(function () use ($agent, $turn, $agentId, $agentName): void {
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
            }, fn (Emitter $emit) => $agent->respond($turn, $emit), $agentId, $agentName);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
