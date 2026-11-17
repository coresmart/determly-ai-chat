<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class Orders
{
    /**
     * @return list<array{id: string, customer: string, status: string, total: string}>
     */
    public static function all(): array
    {
        return [
            ['id' => '1001', 'customer' => 'Avery Chen', 'status' => 'shipped', 'total' => '$86.00'],
            ['id' => '1002', 'customer' => 'Jordan Blake', 'status' => 'open', 'total' => '$42.50'],
            ['id' => '1003', 'customer' => 'Sam Okonkwo', 'status' => 'paid', 'total' => '$129.00'],
            ['id' => '1004', 'customer' => 'Riley Nguyen', 'status' => 'shipped', 'total' => '$18.75'],
        ];
    }

    /**
     * @return list<array{id: string, customer: string, status: string, total: string}>
     */
    public static function query(string $status): array
    {
        $status = strtolower(trim($status));
        if ($status === '' || $status === 'all') {
            return self::all();
        }

        return array_values(array_filter(
            self::all(),
            fn (array $row): bool => $row['status'] === $status,
        ));
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function markdown(array $rows): string
    {
        $lines = [
            '| Order | Customer | Status | Total |',
            '| --- | --- | --- | --- |',
        ];
        foreach ($rows as $row) {
            $lines[] = '| '.self::cell($row['id'] ?? '').' | '.self::cell($row['customer'] ?? '').' | '.self::cell($row['status'] ?? '').' | '.self::cell($row['total'] ?? '').' |';
        }

        return implode("\n", $lines);
    }

    private static function cell(mixed $value): string
    {
        return str_replace('|', '/', trim((string) $value));
    }
}
