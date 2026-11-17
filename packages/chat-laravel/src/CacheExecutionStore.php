<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Run\Execution;
use Determly\Chat\Run\ExecutionStore;
use Illuminate\Contracts\Cache\Repository;

final class CacheExecutionStore implements ExecutionStore
{
    public function __construct(
        private readonly Repository $cache,
        private readonly int $seconds = 3600,
    ) {}

    public function put(Execution $execution): void
    {
        $this->cache->put($this->key($execution->id), $execution->toArray(), $this->seconds);
    }

    public function find(string $id): ?Execution
    {
        $stored = $this->cache->get($this->key($id));
        if (! is_array($stored)) {
            return null;
        }

        return Execution::fromArray($stored);
    }

    public function forget(string $id): void
    {
        $this->cache->forget($this->key($id));
    }

    public function allow(string $tool): void
    {
        $allowed = $this->cache->get($this->allowKey(), []);
        if (! is_array($allowed)) {
            $allowed = [];
        }
        $allowed[$tool] = true;
        $this->cache->forever($this->allowKey(), $allowed);
    }

    public function allowed(string $tool): bool
    {
        $allowed = $this->cache->get($this->allowKey(), []);

        return is_array($allowed) && isset($allowed[$tool]);
    }

    private function key(string $id): string
    {
        return 'determly-chat:execution:'.$id;
    }

    private function allowKey(): string
    {
        return 'determly-chat:allowed-tools';
    }
}
