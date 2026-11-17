<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

final class Step
{
    /**
     * @param  array<string, mixed>  $input
     * @param  list<string|array{label: string, prompt?: string, description?: string}>  $suggestions
     */
    private function __construct(
        public readonly string $kind,
        public readonly string $text = '',
        public readonly string $tool = '',
        public readonly array $input = [],
        public readonly array $suggestions = [],
    ) {}

    public static function text(string $text): self
    {
        return new self('text', $text);
    }

    public static function reasoning(string $text): self
    {
        return new self('reasoning', $text);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function tool(string $name, array $input = []): self
    {
        return new self('tool', tool: $name, input: $input);
    }

    /**
     * @param  list<string|array{label: string, prompt?: string, description?: string}>  $items
     */
    public static function suggestions(array $items): self
    {
        return new self('suggestions', suggestions: $items);
    }
}
