<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

use Determly\Chat\Gallery\Tools\LookupOrder;
use Determly\Chat\Gallery\Tools\QueryOrders;
use Determly\Chat\Gallery\Tools\RefundOrder;
use Determly\Chat\Run\Responder;
use Determly\Chat\ToolRegistry;
use Illuminate\Support\ServiceProvider;

final class GalleryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ExampleSlug::class);
        $this->app->booting(function (): void {
            config([
                'determly-chat.ai' => 'echo',
                'determly-chat.inject' => false,
                'cache.default' => 'file',
                'session.driver' => 'file',
            ]);
        });
    }

    public function boot(): void
    {
        $this->app->bind(Responder::class, fn ($app): Responder => new GalleryResponder($app->make(ExampleSlug::class)));

        $tools = $this->app->make(ToolRegistry::class);
        foreach ([new QueryOrders, new RefundOrder, new LookupOrder] as $tool) {
            if (! $tools->has($tool->name())) {
                $tools->register($tool, 'gallery');
            }
        }
    }
}
