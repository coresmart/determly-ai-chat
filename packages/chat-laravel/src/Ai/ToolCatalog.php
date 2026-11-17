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
                $tools[] = new PageTool($name);
                $seen[$name] = true;
            }
        }

        return $tools;
    }
}
