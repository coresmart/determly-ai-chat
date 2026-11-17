<?php

declare(strict_types=1);

namespace Determly\Chat\Examples;

use Determly\Chat\Tool;

/**
 * Server tool for the support chat example.
 * The same class is advertised to Laravel AI and to Prism. The runner calls handle() once.
 */
final class LookupOrder implements Tool
{
    public function name(): string
    {
        return 'lookup_order';
    }

    public function description(): string
    {
        return 'Look up one order by its id and return the status. This does not change the order.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => [
                    'type' => 'string',
                    'description' => 'The order id, such as 1001.',
                ],
            ],
            'required' => ['order_id'],
        ];
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function handle(array $parameters): mixed
    {
        $id = trim((string) ($parameters['order_id'] ?? ''));

        return [
            'order_id' => $id,
            'status' => $id === '1001' ? 'shipped' : 'unknown',
        ];
    }
}
