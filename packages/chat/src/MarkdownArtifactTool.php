<?php

declare(strict_types=1);

namespace Determly\Chat;

/** Opens a markdown note in the Persona artifact pane. The runner emits the frames. */
final class MarkdownArtifactTool implements Tool
{
    public function name(): string
    {
        return Artifacts::MARKDOWN;
    }

    public function description(): string
    {
        return 'Show a longer markdown note in the side pane. Use this instead of pasting the note into the reply.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Short title for the note.'],
                'content' => ['type' => 'string', 'description' => 'Markdown body of the note.'],
            ],
            'required' => ['title', 'content'],
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
