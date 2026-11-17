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
     * @param  array<string, array{origin?: string, description?: string, parameters?: array<string, mixed>}>  $clientToolMeta
     */
    public function __construct(
        public readonly array $messages,
        public readonly array $context = [],
        public readonly array $metadata = [],
        public readonly array $clientTools = [],
        public readonly array $clientToolMeta = [],
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

        [$names, $meta] = self::clientTools($body['clientTools'] ?? []);

        return new self(
            Messages::flatten(array_is_list($messages) ? $messages : throw new InvalidChat('messages must be a list.')),
            self::record($body['context'] ?? [], 'context'),
            self::record($body['metadata'] ?? [], 'metadata'),
            $names,
            $meta,
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
     * Persona sends page tools as objects. A string name is still accepted.
     * `origin: webmcp` is kept so the pause can tell the widget to run the tool.
     *
     * @return array{0: list<string>, 1: array<string, array{origin?: string, description?: string, parameters?: array<string, mixed>}>}
     */
    private static function clientTools(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidChat('clientTools must be a list.');
        }
        $names = [];
        $meta = [];
        foreach ($value as $tool) {
            $name = is_string($tool) ? $tool : (is_array($tool) ? ($tool['name'] ?? null) : null);
            if (! is_string($name) || trim($name) === '') {
                throw new InvalidChat('Each client tool needs a name.');
            }
            $name = trim($name);
            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }
            if (! is_array($tool)) {
                continue;
            }
            $detail = [];
            $origin = $tool['origin'] ?? null;
            if ($origin === 'webmcp' || $origin === 'sdk') {
                $detail['origin'] = $origin;
            }
            if (isset($tool['description']) && is_string($tool['description']) && trim($tool['description']) !== '') {
                $detail['description'] = trim($tool['description']);
            }
            $schema = $tool['parametersSchema'] ?? $tool['parameters'] ?? null;
            if (is_array($schema) && ! array_is_list($schema)) {
                $detail['parameters'] = $schema;
            }
            if ($detail !== []) {
                $meta[$name] = $detail;
            }
        }

        return [$names, $meta];
    }
}
