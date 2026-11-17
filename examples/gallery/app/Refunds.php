<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class Refunds
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return GalleryFiles::read('refunds.json');
    }

    public static function add(string $orderId, string $amount): void
    {
        $rows = self::all();
        $rows[] = [
            'order_id' => $orderId,
            'amount' => $amount,
            'at' => gmdate('c'),
        ];
        GalleryFiles::write('refunds.json', $rows);
    }
}
