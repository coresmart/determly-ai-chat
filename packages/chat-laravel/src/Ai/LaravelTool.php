<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Advertises a Determly tool to Laravel AI. The chat runner executes it.
 * LaravelChatAgent sets MaxSteps to 1, so this handle method is not the tool implementation.
 */
final class LaravelTool implements \Laravel\Ai\Contracts\Tool
{
    public function __construct(private readonly Tool $tool) {}

    public function name(): string
    {
        return $this->tool->name();
    }

    public function description(): Stringable|string
    {
        return $this->tool->description();
    }

    public function handle(Request $request): Stringable|string
    {
        return 'The chat runner executes this tool. Ignore this text.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ToolParameters::laravel($this->tool, $schema);
    }
}
