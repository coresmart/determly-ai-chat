<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\InvalidChat;

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
        string $target = 'body',
        string $mount = 'floating',
        string $dockSide = 'right',
        string $dockWidth = '420px',
        bool $artifacts = true,
        bool $askUser = true,
        Theme|array|null $theme = null,
    ): string {
        if (! in_array($mount, ['floating', 'docked', 'fullscreen'], true)) {
            throw new InvalidChat('Chat mount must be floating, docked, or fullscreen.');
        }
        if (! in_array($dockSide, ['left', 'right'], true)) {
            throw new InvalidChat('Chat dock side must be left or right.');
        }

        $launcher = [
            'enabled' => $mount !== 'fullscreen',
            'title' => $title,
            'subtitle' => $subtitle,
        ];
        if ($mount === 'docked') {
            $launcher['mountMode'] = 'docked';
            $launcher['dock'] = ['side' => $dockSide, 'width' => $dockWidth];
        }
        if ($mount === 'fullscreen') {
            $launcher['fullHeight'] = true;
        }

        $config = [
            'target' => $target,
            'apiUrl' => $apiUrl,
            'config' => [
                'apiUrl' => $apiUrl,
                'launcher' => $launcher,
                'suggestions' => [
                    'starters' => [
                        'items' => ['What can you do?', 'What tools are available?'],
                    ],
                    'followUps' => [
                        'enabled' => true,
                        'expose' => true,
                    ],
                ],
                'features' => [
                    'showReasoning' => true,
                    'showToolCalls' => true,
                    'artifacts' => [
                        'enabled' => $artifacts,
                        'allowedTypes' => ['markdown', 'component'],
                    ],
                    'askUserQuestion' => [
                        'enabled' => $askUser,
                        'expose' => $askUser,
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
        foreach (Theme::resolve($theme)->persona() as $key => $value) {
            $config['config'][$key] = $value;
        }

        $json = json_encode(
            $config,
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
        $components = file_get_contents(__DIR__.'/../resources/js/components.js');
        if ($components === false) {
            throw new InvalidChat('Chat components script is missing.');
        }
        $approve = $webmcp ? <<<'JS'
            window.siteAgentConfig.config.webmcp.autoApprove = function (info) {
              return !!(info.annotations && info.annotations.readOnlyHint === true);
            };
            JS : '';
        $src = 'https://cdn.jsdelivr.net/npm/@runtypelabs/persona@'.self::VERSION.'/dist/install.global.js';

        return <<<HTML
            <!-- determly-chat-widget -->
            <script>
            {$components}
            window.siteAgentConfig = {$json};
            window.siteAgentConfig.config.components = window.DetermlyChatComponents;
            window.siteAgentConfig.config.streamParser = window.DetermlyChatStreamParser;
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
