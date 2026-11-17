<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class Activity
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return GalleryFiles::read('activity.json');
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function record(string $event, array $fields): void
    {
        $type = trim((string) ($fields['type'] ?? ''));
        $messageId = trim((string) ($fields['messageId'] ?? ''));
        $summary = implode(' · ', array_values(array_filter(
            [$type, $messageId],
            fn (string $part): bool => $part !== '',
        )));
        $rows = self::all();
        $rows[] = [
            'event' => $event,
            'summary' => $summary !== '' ? $summary : 'recorded',
            'at' => gmdate('c'),
        ];
        GalleryFiles::write('activity.json', array_slice($rows, -50));
    }
}
