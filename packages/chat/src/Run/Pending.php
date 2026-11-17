<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

final class Pending
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $reason,
        public readonly string $approvalId,
        public readonly string $toolCallId,
        public readonly string $toolName,
        public readonly array $parameters,
        public readonly string $description,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'approvalId' => $this->approvalId,
            'toolCallId' => $this->toolCallId,
            'toolName' => $this->toolName,
            'parameters' => $this->parameters,
            'description' => $this->description,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $parameters = $data['parameters'] ?? [];

        return new self(
            (string) ($data['reason'] ?? ''),
            (string) ($data['approvalId'] ?? ''),
            (string) ($data['toolCallId'] ?? ''),
            (string) ($data['toolName'] ?? ''),
            is_array($parameters) ? $parameters : [],
            (string) ($data['description'] ?? ''),
        );
    }
}
