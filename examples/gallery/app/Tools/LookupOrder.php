<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Tools;

use Determly\Chat\Gallery\Orders;
use Determly\Chat\Tool;

final class LookupOrder implements Tool
{
    public function name(): string
    {
        return 'lookup_order';
    }

    public function description(): string
    {
        return 'Look up one order by id and return the customer, status, and total.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => ['type' => 'string', 'description' => 'The order id, such as 1001.'],
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
        $id = (string) ($parameters['order_id'] ?? '');
        $row = Orders::find($id);
        if ($row === null) {
            return ['found' => false, 'order_id' => $id];
        }

        return ['found' => true] + $row;
    }
}
