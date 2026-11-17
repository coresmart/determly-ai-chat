<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

use Determly\Chat\Laravel\Theme;
use Determly\Chat\Laravel\Widget;

final class GalleryWidget
{
    public static function html(string $slug): string
    {
        $example = Catalog::get($slug);
        $html = Widget::html(
            '/examples/'.$slug.'/dispatch',
            $example['title'],
            $example['subtitle'],
            theme: $slug === 'theme' ? self::theme() : null,
        );
        $starters = json_encode(
            $example['starters'],
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );
        $extra = <<<JS
            window.siteAgentConfig.config.suggestions.starters.items = {$starters};
            window.siteAgentConfig.config.launcher.autoExpand = true;
            window.siteAgentConfig.config.persistState = false;
            JS;
        if ($slug === 'feedback') {
            $extra .= <<<'JS'
                window.siteAgentConfig.config.messageActions = {enabled:true,showCopy:true,showUpvote:true,showDownvote:true,visibility:"always"};
                JS;
        }

        return self::starters($html, $extra);
    }

    private static function theme(): Theme
    {
        return Theme::make()
            ->accent('#0f766e')
            ->neutral('#f8fafc')
            ->font('Georgia, "Times New Roman", serif')
            ->radius('lg')
            ->density('comfortable')
            ->elevation('subtle')
            ->colors([
                'surface' => '#ffffff',
                'text' => '#134e4a',
            ]);
    }

    private static function starters(string $html, string $extra): string
    {
        return str_replace(
            'window.siteAgentConfig.config.streamParser = window.DetermlyChatStreamParser;',
            "window.siteAgentConfig.config.streamParser = window.DetermlyChatStreamParser;\n".$extra,
            $html,
        );
    }
}
