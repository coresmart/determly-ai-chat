<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * A Laravel AI tool for one Determly tool. handle() runs that tool.
 * The Persona responder does not use this class. It uses LaravelTool, whose handle is a stub,
 * because the chat runner is what executes the tool during a turn.
 */
final class EquivalentLaravelTool implements \Laravel\Ai\Contracts\Tool
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
        return ToolEquivalents::encode($this->tool->handle($request->all()));
    }

    public function schema(JsonSchema $schema): array
    {
        return ToolParameters::laravel($this->tool, $schema);
    }
}
