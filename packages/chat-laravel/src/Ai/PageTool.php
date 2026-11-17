<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Tool;

/**
 * A tool the page named in clientTools. The runner pauses for the browser. This class only advertises it to the model.
 */
final class PageTool implements Tool
{
    public function __construct(private readonly string $toolName) {}

    public function name(): string
    {
        return $this->toolName;
    }

    public function description(): string
    {
        return 'Runs in the browser. The server does not execute it. Put the arguments in input as JSON.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'input' => [
                    'type' => 'string',
                    'description' => 'JSON arguments for the page tool.',
                ],
            ],
        ];
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function handle(array $parameters): mixed
    {
        return '';
    }
}
