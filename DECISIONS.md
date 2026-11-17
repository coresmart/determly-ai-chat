# Decisions

## This repository

https://github.com/coresmart/determly-ai-chat is a Composer monorepo, MIT licensed, matching the `LICENSE` file. The root `composer.json` is a private workspace named `determly/ai-chat-workspace`. It is not an installable package.

Installable libraries live under `packages/`, each with its own `composer.json`:

- `determly/chat` is the Persona wire and has no Laravel dependency.
- `determly/chat-laravel` depends on `determly/chat` and mounts the widget.

An app requires those packages by path. Requiring the GitHub repository itself does not install them, because Composer only reads the root `composer.json`.

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
