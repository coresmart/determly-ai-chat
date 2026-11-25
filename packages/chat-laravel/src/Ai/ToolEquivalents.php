<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Tool;
use Prism\Prism\Tool as PrismTool;
use Stringable;

/**
 * Laravel AI and Prism copies of a Determly tool. Both copies call the same handle().
 * Use these when an agent or a Prism request should run the tool itself.
 * The Persona responders keep their own copies that only advertise the schema.
 */
final class ToolEquivalents
{
    public static function laravel(Tool $tool): EquivalentLaravelTool
    {
        return new EquivalentLaravelTool($tool);
    }

    public static function prism(Tool $tool): PrismTool
    {
        return ToolParameters::prism($tool, execute: true);
    }

    public static function encode(mixed $result): string
    {
        if (is_string($result)) {
            return $result;
        }
        if ($result instanceof Stringable) {
            return (string) $result;
        }
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '';
    }
}
