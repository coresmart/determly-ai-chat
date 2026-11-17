<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Agent;
use Determly\Chat\EchoAgent;
use Determly\Chat\InvalidChat;
use Determly\Chat\Laravel\Ai\AiChoice;
use Determly\Chat\Laravel\Ai\Assistant;
use Determly\Chat\Laravel\Ai\ConfigurationResponder;
use Determly\Chat\Laravel\Ai\LaravelAiResponder;
use Determly\Chat\Laravel\Ai\PrismResponder;
use Determly\Chat\Run\EchoResponder;
use Determly\Chat\Run\ExecutionStore;
use Determly\Chat\Run\Responder;
use Determly\Chat\Run\Runner;
use Determly\Chat\ToolRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/determly-chat.php', 'determly-chat');
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(StackDetector::class);
        $this->app->singleton(HtmlInjector::class);
        $this->app->singleton(ExecutionStore::class, fn (): ExecutionStore => new CacheExecutionStore(
            $this->app->make('cache')->store(),
        ));
        $this->app->bind(Agent::class, EchoAgent::class);
        $this->app->bind(Responder::class, function ($app): Responder {
            try {
                $choice = AiChoice::resolve(
                    (string) config('determly-chat.ai', AiChoice::AUTO),
                    class_exists(\Laravel\Ai\Ai::class),
                    class_exists(\Prism\Prism\Prism::class),
                );
            } catch (InvalidChat $error) {
                return new ConfigurationResponder($error->getMessage());
            }

            $instructions = (string) config('determly-chat.instructions', Assistant::INSTRUCTIONS);
            $provider = AiChoice::present(config('determly-chat.provider'));
            $model = AiChoice::present(config('determly-chat.model'));

            return match ($choice) {
                AiChoice::LARAVEL => new LaravelAiResponder($app->make(ToolRegistry::class), $instructions, $provider, $model),
                AiChoice::PRISM => new PrismResponder($app->make(ToolRegistry::class), $instructions, $provider, $model),
                default => new EchoResponder,
            };
        });
        $this->app->singleton(Runner::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'determly-chat');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([ChatStatusCommand::class]);
        }

        Blade::component('determly-chat::widget', 'determly-chat');

        if (config('determly-chat.inject')) {
            $this->app['router']->pushMiddlewareToGroup('web', InjectWidget::class);
        }
    }
}
