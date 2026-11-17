<?php

declare(strict_types=1);

namespace Determly\Chat\Wire;

interface Writer
{
    public function write(string $chunk): void;
}
