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
        if ($this->reportAi() !== self::SUCCESS) {
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
            $this->error('Prism needs DETERMINLY_CHAT_PROVIDER and DETERMINLY_CHAT_MODEL.');

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
