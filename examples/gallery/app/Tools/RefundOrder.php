<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Tools;

use Determly\Chat\Gallery\Refunds;
use Determly\Chat\Tool;

final class RefundOrder implements Tool
{
    public int $runs = 0;

    public function name(): string
    {
        return 'refund_order';
    }

    public function description(): string
    {
        return 'Refund one order. This charges the order back and needs approval before it runs.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => ['type' => 'string'],
                'amount' => ['type' => 'string'],
            ],
            'required' => ['order_id'],
        ];
    }

    public function requiresApproval(): bool
    {
        return true;
    }

    public function handle(array $parameters): mixed
    {
        $this->runs++;
        $orderId = (string) ($parameters['order_id'] ?? '');
        $amount = (string) ($parameters['amount'] ?? '');
        Refunds::add($orderId, $amount);

        return [
            'refunded' => true,
            'order_id' => $orderId,
            'amount' => $amount,
        ];
    }
}
