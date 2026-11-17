<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

interface ExecutionStore
{
    public function put(Execution $execution): void;

    public function find(string $id): ?Execution;

    public function forget(string $id): void;

    public function allow(string $tool): void;

    public function allowed(string $tool): bool;
}
