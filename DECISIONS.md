# Decisions

## This repository

https://github.com/coresmart/determly-ai-chat is the home of the Determly chat. It is MIT licensed, matching the `LICENSE` file.

The root Composer package is `determly/chat-laravel`. Installing this Git repository gives a Laravel app the wire and the mount together. `packages/chat` stays a Laravel-free package so another chat can use the wire without Illuminate.

## PHP 8.3

The packages require PHP 8.3. Laravel 12 and 13 both run on it.

## One widget for every installed stack

The UI is the Persona widget from https://www.persona-chat.dev (`@runtypelabs/persona` 4.39.0). The wire matches `packages/persona-wire/src/index.ts` in `runtypelabs/persona` at that widget version.

There is no Livewire package, React package, or Vue package. Those would wrap the same vanilla widget and drift. `determly/chat-laravel` detects the installed stack and mounts the same script.

- Filament wins when Livewire is also installed, because Filament apps include Livewire.
- The script is injected into full HTML responses.
- Livewire update requests and Inertia JSON visits are left alone.
- The dispatch route is same-origin and CSRF-exempt so the widget can post. The session cookie still applies.

Until an AI library is bound, dispatch uses `EchoAgent`, which repeats the latest user message. An app replaces the `Determly\Chat\Agent` binding. Tools from any recipe register on `ToolRegistry`. A duplicate tool name is refused.
