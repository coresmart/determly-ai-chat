<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Run\Runner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ApproveController
{
    public function __invoke(Request $request, Runner $runner): StreamedResponse
    {
        $body = $request->json()->all();
        $executionId = is_array($body) ? (string) ($body['executionId'] ?? '') : '';
        $approvalId = is_array($body) ? (string) ($body['approvalId'] ?? '') : '';
        $decision = is_array($body) ? (string) ($body['decision'] ?? '') : '';
        $remember = is_array($body) && ($body['remember'] ?? false) === true;

        return Sse::stream(
            fn ($emit) => $runner->decide($executionId, $approvalId, $decision, $emit, $remember),
            $executionId !== '' ? $executionId : null,
        );
    }
}
