<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

use Determly\Chat\InvalidChat;

final class StackDetector
{
    /**
     * @param  list<string>  $composer
     * @param  list<string>  $npm
     */
    public function detect(array $composer, array $npm, ?string $override = null): Stack
    {
        if ($override !== null && $override !== '') {
            $stack = Stack::tryFrom($override);
            if (! $stack instanceof Stack) {
                throw new InvalidChat('Unknown chat stack ['.$override.'].');
            }

            return $stack;
        }

        $composer = array_fill_keys($composer, true);
        $npm = array_fill_keys($npm, true);

        if (isset($composer['filament/filament'])) {
            return Stack::Filament;
        }
        if (isset($composer['livewire/livewire'])) {
            return Stack::Livewire;
        }

        $inertia = isset($composer['inertiajs/inertia-laravel']);
        $react = isset($npm['@inertiajs/react']);
        $vue = isset($npm['@inertiajs/vue3']);
        if ($inertia && $react && $vue) {
            return Stack::Inertia;
        }
        if ($inertia && $react) {
            return Stack::InertiaReact;
        }
        if ($inertia && $vue) {
            return Stack::InertiaVue;
        }

        return Stack::Blade;
    }
}
