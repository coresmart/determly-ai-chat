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

    public static function html(
        string $apiUrl,
        string $title = 'Assistant',
        string $subtitle = 'Ask a question',
        string $windowKey = 'determlyChat',
        bool $webmcp = true,
    ): string {
        $config = [
            'target' => 'body',
            'apiUrl' => $apiUrl,
            'config' => [
                'apiUrl' => $apiUrl,
                'launcher' => [
                    'enabled' => true,
                    'title' => $title,
                    'subtitle' => $subtitle,
                ],
                'suggestions' => [
                    'starters' => [
                        'items' => ['What can you do?', 'What tools are available?'],
                    ],
                ],
                'persistState' => true,
            ],
        ];
        if ($windowKey !== '') {
            $config['windowKey'] = $windowKey;
        }
        if ($webmcp) {
            $config['config']['webmcp'] = ['enabled' => true];
        }
        $json = json_encode(
            $config,
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
        $approve = $webmcp ? <<<'JS'
            window.siteAgentConfig.config.webmcp.autoApprove = function (info) {
              return !!(info.annotations && info.annotations.readOnlyHint === true);
            };
            JS : '';
        $src = 'https://cdn.jsdelivr.net/npm/@runtypelabs/persona@'.self::VERSION.'/dist/install.global.js';

        return <<<HTML
            <!-- determly-chat-widget -->
            <script>
            window.siteAgentConfig = {$json};
            window.siteAgentConfig.config.getHeaders = function () {
              var token = document.querySelector('meta[name="csrf-token"]');
              var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/event-stream' };
              if (token && token.content) headers['X-CSRF-TOKEN'] = token.content;
              return headers;
            };
            {$approve}
            </script>
            <script src="{$src}"></script>
            HTML;
    }
}
