<?php

declare(strict_types=1);

namespace Determly\Chat;

/** Opens a registered component in the Persona artifact pane. The runner emits the frames. */
final class ComponentArtifactTool implements Tool
{
    public function name(): string
    {
        return Artifacts::COMPONENT;
    }

    public function description(): string
    {
        return 'Show a registered component in the side pane. component is DynamicForm, ProductCard, Notice, or another name the page registered. props is a JSON object.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Short title for the pane.'],
                'component' => ['type' => 'string', 'description' => 'Registered component name.'],
                'props' => ['type' => 'string', 'description' => 'JSON object of props for that component.'],
            ],
            'required' => ['title', 'component', 'props'],
        ];
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function handle(array $parameters): mixed
    {
        return 'Shown to the user as an artifact.';
    }
}
