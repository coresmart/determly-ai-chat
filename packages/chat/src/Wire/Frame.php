<?php

declare(strict_types=1);

namespace Determly\Chat\Wire;

final class Frame
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $event,
        public readonly array $data,
    ) {}

    /**
     * Parse one SSE document into frames. Used by tests and by other chats
     * that receive a Persona stream.
     *
     * @return list<self>
     */
    public static function parse(string $sse): array
    {
        $frames = [];
        foreach (preg_split("/\n\n/", trim($sse)) ?: [] as $block) {
            if (trim($block) === '') {
                continue;
            }
            $event = null;
            $data = null;
            foreach (preg_split("/\n/", $block) ?: [] as $line) {
                if (str_starts_with($line, 'event: ')) {
                    $event = substr($line, 7);
                }
                if (str_starts_with($line, 'data: ')) {
                    $decoded = json_decode(substr($line, 6), true);
                    $data = is_array($decoded) ? $decoded : [];
                }
            }
            if ($event === null || $data === null) {
                continue;
            }
            $frames[] = new self($event, $data);
        }

        return $frames;
    }
}
