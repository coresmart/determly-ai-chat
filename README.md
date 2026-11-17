# Determly chat

Persona chat for Determly apps. One widget, one SSE endpoint, and whichever Laravel stack is already installed.

Repository: https://github.com/coresmart/determly-ai-chat

| Path | Package | Role |
|---|---|---|
| Repository root | `determly/chat-laravel` | What a Laravel app requires. Includes the wire and the Laravel mount. |
| `packages/chat` | `determly/chat` | Persona SSE wire only. No Laravel dependency. |
| `packages/chat-laravel` | `determly/chat-laravel` | Dispatch route, HTML mount, stack report. |

Blade, Livewire, Inertia React, Inertia Vue, and Filament all load `@runtypelabs/persona` 4.39.0. The stack detector reports which app is installed. It does not swap the widget. Filament wins when Livewire is also present.

Other chats use `Determly\Chat\Wire\Session` and the same Persona event stream. Recipes register tools on `Determly\Chat\ToolRegistry`. They do not ship a second transcript.

Details: [docs/chat.md](docs/chat.md). Decisions: [DECISIONS.md](DECISIONS.md).

```bash
composer install
composer test
```

PHP 8.3 or newer. MIT licensed.
