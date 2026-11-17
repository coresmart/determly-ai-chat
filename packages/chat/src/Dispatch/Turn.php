<?php

declare(strict_types=1);

namespace Determly\Chat\Dispatch;

use Determly\Chat\InvalidChat;

final class Turn
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $metadata
     * @param  list<string>  $clientTools
     */
    public function __construct(
        public readonly array $messages,
        public readonly array $context = [],
        public readonly array $metadata = [],
        public readonly array $clientTools = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        $messages = $body['messages'] ?? [];
        if (! is_array($messages)) {
            throw new InvalidChat('messages must be a list.');
        }

        return new self(
            Messages::flatten(array_is_list($messages) ? $messages : throw new InvalidChat('messages must be a list.')),
            self::record($body['context'] ?? [], 'context'),
            self::record($body['metadata'] ?? [], 'metadata'),
            self::clientTools($body['clientTools'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function record(mixed $value, string $field): array
    {
        if (! is_array($value) || (array_is_list($value) && $value !== [])) {
            throw new InvalidChat($field.' must be an object.');
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private static function clientTools(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidChat('clientTools must be a list.');
        }
        $names = [];
        foreach ($value as $tool) {
            $name = is_string($tool) ? $tool : (is_array($tool) ? ($tool['name'] ?? null) : null);
            if (! is_string($name) || trim($name) === '') {
                throw new InvalidChat('Each client tool needs a name.');
            }
            $names[] = $name;
        }

        return $names;
    }
}
