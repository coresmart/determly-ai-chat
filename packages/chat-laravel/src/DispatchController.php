<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Run\Runner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DispatchController
{
    public function __invoke(Request $request, Runner $runner): StreamedResponse
    {
        $body = $request->json()->all();
        $turn = Turn::fromArray(is_array($body) ? $body : []);

        return Sse::stream(fn ($emit) => $runner->dispatch($turn, $emit));
    }
}
