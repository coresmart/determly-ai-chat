<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\InvalidChat;
use Determly\Chat\Laravel\Ai\AiChoice;
use Determly\Chat\ToolRegistry;
use Illuminate\Console\Command;

final class ChatStatusCommand extends Command
{
    protected $signature = 'determly:chat';

    protected $description = 'Show the Persona chat stack, dispatch URL, and registered tools';

    public function handle(StackDetector $detector, ToolRegistry $tools): int
    {
        $stack = $detector->detect(
            $this->composerPackages(),
            $this->npmPackages(),
            config('determly-chat.stack'),
        );

        $this->line('Widget: @runtypelabs/persona@'.Widget::VERSION);
        $this->line('Stack: '.$stack->value);
        $this->line($stack->summary());
        $this->line('Dispatch: '.url((string) config('determly-chat.path')));
        $windowKey = (string) config('determly-chat.window_key', 'determlyChat');
        $this->line('Window: '.($windowKey !== '' ? 'window.'.$windowKey : 'persona:chat-ready'));
        $this->line('WebMCP: '.(config('determly-chat.webmcp') ? 'on' : 'off'));
        $mount = (string) config('determly-chat.mount', 'floating');
        $target = (string) config('determly-chat.target', 'body');
        $this->line('Mount: '.$mount.' target '.$target);
        if ($mount === 'docked' && in_array($target, ['body', 'html'], true)) {
            $this->error('A docked chat cannot target body or html. Set DETERMLY_CHAT_TARGET to a page region.');

            return self::FAILURE;
        }
        $this->line('Artifacts: '.(config('determly-chat.artifacts') ? 'on' : 'off'));
        $this->line('Ask user: '.(config('determly-chat.ask_user') ? 'on' : 'off'));
        $this->line('Components: DynamicForm, ProductCard, Notice');
        if ($this->reportTheme() !== self::SUCCESS || $this->reportAi() !== self::SUCCESS) {
            return self::FAILURE;
        }

        $owners = $tools->owners();
        if ($owners === []) {
            $this->line('Tools: none registered');
        } else {
            foreach ($owners as $name => $owner) {
                $this->line('Tool: '.$name.' ('.$owner.')');
            }
        }

        return self::SUCCESS;
    }

    private function reportTheme(): int
    {
        try {
            $persona = Theme::resolve(config('determly-chat.appearance'))->persona();
        } catch (InvalidChat $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        if ($persona === []) {
            $this->line('Theme: Persona defaults');

            return self::SUCCESS;
        }

        $scheme = is_string($persona['colorScheme'] ?? null) ? $persona['colorScheme'] : 'light';
        $this->line('Theme: '.$scheme);
        $brand = is_array($persona['brand'] ?? null) ? $persona['brand'] : [];
        foreach (['accent', 'neutral', 'font', 'radius', 'density', 'elevation'] as $field) {
            if (is_string($brand[$field] ?? null) && $brand[$field] !== '') {
                $this->line(ucfirst($field).': '.$brand[$field]);
            }
        }
        if (isset($persona['theme'])) {
            $this->line('Tokens: theme');
        }
        if (isset($persona['darkTheme'])) {
            $this->line('Tokens: dark theme');
        }

        return self::SUCCESS;
    }

    private function reportAi(): int
    {
        try {
            $choice = AiChoice::resolve(
                (string) config('determly-chat.ai', AiChoice::AUTO),
                class_exists(\Laravel\Ai\Ai::class),
                class_exists(\Prism\Prism\Prism::class),
            );
        } catch (InvalidChat $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $this->line('AI: '.$choice);
        if ($choice === AiChoice::ECHO) {
            return self::SUCCESS;
        }

        $provider = AiChoice::present(config('determly-chat.provider'));
        $model = AiChoice::present(config('determly-chat.model'));
        $this->line('Provider: '.($provider ?? 'library default'));
        $this->line('Model: '.($model ?? 'library default'));
        if ($choice === AiChoice::PRISM && ($provider === null || $model === null)) {
            $this->error('Prism needs DETERMLY_CHAT_PROVIDER and DETERMLY_CHAT_MODEL.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function composerPackages(): array
    {
        $lock = base_path('composer.lock');
        if (! is_file($lock)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($lock), true);
        if (! is_array($json)) {
            return [];
        }
        $names = [];
        foreach ([...($json['packages'] ?? []), ...($json['packages-dev'] ?? [])] as $package) {
            if (is_array($package) && is_string($package['name'] ?? null)) {
                $names[] = $package['name'];
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function npmPackages(): array
    {
        $path = base_path('package.json');
        if (! is_file($path)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            return [];
        }
        $names = [];
        foreach (['dependencies', 'devDependencies'] as $key) {
            if (is_array($json[$key] ?? null)) {
                foreach (array_keys($json[$key]) as $name) {
                    if (is_string($name)) {
                        $names[] = $name;
                    }
                }
            }
        }

        return $names;
    }
}
