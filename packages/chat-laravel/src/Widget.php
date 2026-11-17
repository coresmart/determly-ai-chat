<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

/**
 * The same Persona widget for every Laravel stack.
 * Version pin matches the wire behavior in determly/chat.
 */
final class Widget
{
    public const VERSION = '4.39.0';

    public const MARKER = 'determly-chat-widget';

    public static function html(string $apiUrl, string $title = 'Assistant', string $subtitle = 'Ask a question'): string
    {
        $config = json_encode([
            'target' => 'body',
            'apiUrl' => $apiUrl,
            'config' => [
                'apiUrl' => $apiUrl,
                'launcher' => [
                    'enabled' => true,
                    'title' => $title,
                    'subtitle' => $subtitle,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

        $src = 'https://cdn.jsdelivr.net/npm/@runtypelabs/persona@'.self::VERSION.'/dist/install.global.js';

        return <<<HTML
            <!-- determly-chat-widget -->
            <script>
            window.siteAgentConfig = {$config};
            window.siteAgentConfig.config.getHeaders = function () {
              var token = document.querySelector('meta[name="csrf-token"]');
              var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/event-stream' };
              if (token && token.content) headers['X-CSRF-TOKEN'] = token.content;
              return headers;
            };
            </script>
            <script src="{$src}"></script>
            HTML;
    }
}
