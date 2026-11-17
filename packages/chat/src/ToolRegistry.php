<?php

declare(strict_types=1);

namespace Determly\Chat;

final class ToolRegistry
{
    /**
     * @var array<string, array{tool: Tool, owner: string}>
     */
    private array $tools = [];

    public function register(Tool $tool, string $owner): void
    {
        $name = $tool->name();
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $name) !== 1) {
            throw new InvalidChat('Tool name ['.$name.'] must be a lowercase identifier.');
        }
        if (isset($this->tools[$name])) {
            throw new InvalidChat('Tool ['.$name.'] is already registered by ['.$this->tools[$name]['owner'].'].');
        }
        $this->tools[$name] = ['tool' => $tool, 'owner' => $owner];
    }

    /**
     * @return list<Tool>
     */
    public function all(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $tools[] = $tool['tool'];
        }

        return $tools;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): Tool
    {
        if (! isset($this->tools[$name])) {
            throw new InvalidChat('Tool ['.$name.'] is not registered.');
        }

        return $this->tools[$name]['tool'];
    }

    /**
     * @return array<string, string> tool name => owning package or recipe
     */
    public function owners(): array
    {
        $owners = [];
        foreach ($this->tools as $name => $tool) {
            $owners[$name] = $tool['owner'];
        }

        return $owners;
    }
}
