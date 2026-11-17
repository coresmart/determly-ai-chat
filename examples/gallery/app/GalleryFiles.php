<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class GalleryFiles
{
    public static function path(string $name): string
    {
        $directory = dirname(__DIR__).'/storage/framework/gallery';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Gallery storage is not writable.');
        }

        return $directory.'/'.$name;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function read(string $name): array
    {
        $path = self::path($name);
        if (! is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function write(string $name, array $rows): void
    {
        file_put_contents(
            self::path($name),
            json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]',
            LOCK_EX,
        );
    }
}
