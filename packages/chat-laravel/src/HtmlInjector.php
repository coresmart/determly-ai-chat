<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

final class HtmlInjector
{
    public function inject(string $html, string $widget): string
    {
        if (str_contains($html, Widget::MARKER)) {
            return $html;
        }
        if (preg_match('/<\/body>/i', $html) !== 1) {
            return $html;
        }

        return (string) preg_replace('/<\/body>/i', $widget."\n</body>", $html, 1);
    }
}
