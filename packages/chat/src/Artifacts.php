<?php

declare(strict_types=1);

namespace Determly\Chat;

use Determly\Chat\Wire\Emitter;

/**
 * Persona 4.39.0 artifact frames. The widget hides the matching tool names
 * and opens the side pane from these events.
 */
final class Artifacts
{
    public const MARKDOWN = 'emit_artifact_markdown';

    public const COMPONENT = 'emit_artifact_component';

    public static function handles(string $name): bool
    {
        return $name === self::MARKDOWN || $name === self::COMPONENT;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function show(Emitter $emit, string $name, array $input): string
    {
        $id = 'artifact_'.bin2hex(random_bytes(8));
        if ($name === self::MARKDOWN) {
            $title = self::text($input['title'] ?? null, 'Note');
            $emit->artifactStart($id, 'markdown', $title);
            $emit->artifactDelta($id, self::text($input['content'] ?? null, ''));
            $emit->artifactComplete($id);

            return $title;
        }

        $title = self::text($input['title'] ?? null, 'Panel');
        $component = self::text($input['component'] ?? null, 'Notice');
        $emit->artifactStart($id, 'component', $title, $component);
        $emit->artifactUpdate($id, self::props($input['props'] ?? null), $component);
        $emit->artifactComplete($id);

        return $title;
    }

    private static function text(mixed $value, string $fallback): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private static function props(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($value) || array_is_list($value)) {
            return [];
        }

        return $value;
    }
}
