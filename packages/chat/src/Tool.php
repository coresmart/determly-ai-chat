<?php

declare(strict_types=1);

namespace Determly\Chat;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array;

    public function requiresApproval(): bool;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function handle(array $parameters): mixed;
}
