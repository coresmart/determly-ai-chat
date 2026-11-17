<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

final class MemoryExecutionStore implements ExecutionStore
{
    /**
     * @var array<string, Execution>
     */
    private array $items = [];

    /**
     * @var array<string, true>
     */
    private array $allowed = [];

    public function put(Execution $execution): void
    {
        $this->items[$execution->id] = $execution;
    }

    public function find(string $id): ?Execution
    {
        return $this->items[$id] ?? null;
    }

    public function forget(string $id): void
    {
        unset($this->items[$id]);
    }

    public function allow(string $tool): void
    {
        $this->allowed[$tool] = true;
    }

    public function allowed(string $tool): bool
    {
        return isset($this->allowed[$tool]);
    }
}
