<?php

declare(strict_types=1);

namespace Determly\Chat\Dispatch;

use Determly\Chat\InvalidChat;

final class Messages
{
    /**
     * Flatten a Persona dispatch body into the neutral chat messages adapters consume.
     *
     * @param  list<mixed>  $messages
     * @return list<array{role: string, content: string}>
     */
    public static function flatten(array $messages): array
    {
        if (! array_is_list($messages)) {
            throw new InvalidChat('messages must be a list.');
        }

        $flat = [];
        foreach ($messages as $message) {
            if (! is_array($message)) {
                throw new InvalidChat('Each message must be an object.');
            }
            $role = $message['role'] ?? null;
            if (! in_array($role, ['system', 'user', 'assistant'], true)) {
                continue;
            }
            $flat[] = [
                'role' => $role,
                'content' => self::content($message['content'] ?? ''),
            ];
        }

        return $flat;
    }

    public static function content(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if (! is_array($content)) {
            return (string) ($content ?? '');
        }
        if (! array_is_list($content)) {
            return '';
        }

        $text = '';
        foreach ($content as $part) {
            if (is_array($part) && array_key_exists('text', $part)) {
                $text .= (string) ($part['text'] ?? '');
            }
        }

        return $text;
    }
}
