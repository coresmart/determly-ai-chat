<?php

declare(strict_types=1);

namespace Determly\Chat\Wire;

final class BufferWriter implements Writer
{
    public string $body = '';

    public function write(string $chunk): void
    {
        $this->body .= $chunk;
    }
}
