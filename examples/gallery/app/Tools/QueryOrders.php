<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Tools;

use Determly\Chat\Gallery\Orders;
use Determly\Chat\Tool;

final class QueryOrders implements Tool
{
    public function name(): string
    {
        return 'query_orders';
    }

    public function description(): string
    {
        return 'Query the orders shown on the gallery page. status is all, open, paid, or shipped.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'all, open, paid, or shipped.',
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
        $status = isset($parameters['status']) ? (string) $parameters['status'] : 'all';

        return [
            'status' => $status,
            'rows' => Orders::query($status),
        ];
    }
}
