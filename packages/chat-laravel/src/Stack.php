<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel;

enum Stack: string
{
    case Filament = 'filament';
    case Livewire = 'livewire';
    case InertiaReact = 'inertia-react';
    case InertiaVue = 'inertia-vue';
    case Inertia = 'inertia';
    case Blade = 'blade';

    public function summary(): string
    {
        return match ($this) {
            self::Filament => 'Filament panel HTML and other full HTML responses.',
            self::Livewire => 'Livewire full page loads. Livewire updates are left alone.',
            self::InertiaReact => 'The first Inertia HTML document. React JSON visits are left alone.',
            self::InertiaVue => 'The first Inertia HTML document. Vue JSON visits are left alone.',
            self::Inertia => 'The first Inertia HTML document. Both React and Vue adapters are installed.',
            self::Blade => 'Full HTML responses from the Blade layout.',
        };
    }
}
