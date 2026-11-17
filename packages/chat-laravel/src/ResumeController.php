<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Run\Runner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResumeController
{
    public function __invoke(Request $request, Runner $runner): StreamedResponse
    {
        $body = $request->json()->all();
        $executionId = is_array($body) ? (string) ($body['executionId'] ?? '') : '';
        $outputs = is_array($body) && is_array($body['toolOutputs'] ?? null) ? $body['toolOutputs'] : [];

        return Sse::stream(
            fn ($emit) => $runner->resume($executionId, $outputs, $emit),
            $executionId !== '' ? $executionId : null,
        );
    }
}
