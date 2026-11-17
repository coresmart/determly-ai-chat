<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class ExampleSlug
{
    public function __construct(private readonly ?string $slug = null) {}

    public function current(): string
    {
        if (is_string($this->slug) && $this->slug !== '') {
            return $this->slug;
        }
        if (! function_exists('request')) {
            return '';
        }

        try {
            $value = request()->route('example');
        } catch (\Throwable) {
            return '';
        }

        return is_string($value) ? $value : '';
    }
}
