<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

final class Execution
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  list<string>  $clientTools
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public array $messages,
        public array $clientTools = [],
        public array $context = [],
        public array $metadata = [],
        public ?Pending $pending = null,
        public int $rounds = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'messages' => $this->messages,
            'clientTools' => $this->clientTools,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'pending' => $this->pending?->toArray(),
            'rounds' => $this->rounds,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $pending = $data['pending'] ?? null;

        return new self(
            (string) $data['id'],
            is_array($data['messages'] ?? null) ? $data['messages'] : [],
            is_array($data['clientTools'] ?? null) ? array_values($data['clientTools']) : [],
            is_array($data['context'] ?? null) ? $data['context'] : [],
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            is_array($pending) ? Pending::fromArray($pending) : null,
            (int) ($data['rounds'] ?? 0),
        );
    }
}
