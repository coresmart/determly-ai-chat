<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InjectWidget
{
    public function __construct(
        private readonly HtmlInjector $injector,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! config('determly-chat.inject')) {
            return $response;
        }
        if ($request->headers->has('X-Livewire')) {
            return $response;
        }

        $type = (string) $response->headers->get('Content-Type', '');
        if ($type !== '' && ! str_contains($type, 'text/html')) {
            return $response;
        }
        if (! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent($this->injector->inject($content, trim(view('determly-chat::widget')->render())));

        return $response;
    }
}
