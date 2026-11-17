<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Tests;

use Determly\Chat\InvalidChat;
use Determly\Chat\Laravel\HtmlInjector;
use Determly\Chat\Laravel\Stack;
use Determly\Chat\Laravel\StackDetector;
use Determly\Chat\Laravel\Widget;
use PHPUnit\Framework\TestCase;

final class StackMountTest extends TestCase
{
    public function test_filament_wins_when_livewire_is_also_installed(): void
    {
        $stack = (new StackDetector)->detect(
            ['livewire/livewire', 'filament/filament', 'livewire/flux'],
            [],
        );

        $this->assertSame(Stack::Filament, $stack);
    }

    public function test_livewire_starter_is_detected(): void
    {
        $stack = (new StackDetector)->detect(['livewire/livewire', 'livewire/flux'], []);

        $this->assertSame(Stack::Livewire, $stack);
    }

    public function test_inertia_react_and_vue_are_detected_from_npm(): void
    {
        $detector = new StackDetector;

        $this->assertSame(
            Stack::InertiaReact,
            $detector->detect(['inertiajs/inertia-laravel'], ['@inertiajs/react']),
        );
        $this->assertSame(
            Stack::InertiaVue,
            $detector->detect(['inertiajs/inertia-laravel'], ['@inertiajs/vue3']),
        );
        $this->assertSame(
            Stack::Inertia,
            $detector->detect(['inertiajs/inertia-laravel'], ['@inertiajs/react', '@inertiajs/vue3']),
        );
    }

    public function test_an_app_with_no_front_end_stack_uses_blade(): void
    {
        $this->assertSame(Stack::Blade, (new StackDetector)->detect([], []));
    }

    public function test_stack_override_must_be_a_known_value(): void
    {
        $this->expectException(InvalidChat::class);
        (new StackDetector)->detect(['livewire/livewire'], [], 'react');
    }

    public function test_every_stack_gets_the_same_persona_widget(): void
    {
        $html = Widget::html('/determly/chat/dispatch', 'Assistant', 'Ask a question');

        $this->assertStringContainsString('@runtypelabs/persona@4.39.0/dist/install.global.js', $html);
        $this->assertStringContainsString('/determly/chat/dispatch', $html);
        $this->assertStringContainsString('getHeaders', $html);
        $this->assertStringContainsString('"windowKey":"determlyChat"', $html);
        $this->assertStringContainsString('"webmcp":{"enabled":true}', $html);
        $this->assertStringContainsString('readOnlyHint', $html);
        $this->assertStringContainsString(Widget::MARKER, $html);
        $this->assertStringNotContainsString('livewire', strtolower($html));
        $this->assertStringNotContainsString('inertia', strtolower($html));
        $this->assertStringNotContainsString('filament', strtolower($html));
    }

    public function test_html_inject_is_idempotent_and_skips_documents_without_a_body(): void
    {
        $injector = new HtmlInjector;
        $page = "<html><body><p>Hi</p></body></html>";
        $once = $injector->inject($page, Widget::html('/determly/chat/dispatch'));
        $twice = $injector->inject($once, Widget::html('/determly/chat/dispatch'));

        $this->assertSame(1, substr_count($twice, Widget::MARKER));
        $this->assertSame('{"ok":true}', $injector->inject('{"ok":true}', Widget::html('/determly/chat/dispatch')));
    }
}
