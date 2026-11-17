<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\Agent;
use Determly\Chat\EchoAgent;
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
        $this->app->bind(Agent::class, EchoAgent::class);
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
