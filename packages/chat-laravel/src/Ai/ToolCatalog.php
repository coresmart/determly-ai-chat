<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Tool;
use Determly\Chat\ToolRegistry;

final class ToolCatalog
{
    /**
     * Registry tools plus page tools that are not already registered.
     * A registered name that is also a page tool keeps its schema. The runner still pauses for the page.
     *
     * @return list<Tool>
     */
    public static function forTurn(ToolRegistry $registry, Turn $turn): array
    {
        $tools = $registry->all();
        $seen = [];
        foreach ($tools as $tool) {
            $seen[$tool->name()] = true;
        }
        foreach ($turn->clientTools as $name) {
            if (! isset($seen[$name])) {
                $meta = $turn->clientToolMeta[$name] ?? [];
                $tools[] = new PageTool(
                    $name,
                    is_string($meta['description'] ?? null) ? $meta['description'] : null,
                    is_array($meta['parameters'] ?? null) ? $meta['parameters'] : null,
                );
                $seen[$name] = true;
            }
        }

        return $tools;
    }
}
