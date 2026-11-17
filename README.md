# determly-ai-chat

Determly monorepo for [Persona](https://www.persona-chat.dev) chat. Each installable library lives in `packages/` with its own `composer.json`. The root project is a private Composer workspace that links those packages with a path repository, the same layout as `coresmart/tinyfish-php`.

| Package | Path | Role |
|---|---|---|
| [`determly/chat`](packages/chat) | `packages/chat` | Persona SSE wire. No Laravel dependency. Other chats speak this. |
| [`determly/chat-laravel`](packages/chat-laravel) | `packages/chat-laravel` | Dispatch route and the one widget mount for Blade, Livewire, Inertia, and Filament. |

`determly/chat-laravel` depends on `determly/chat`. Blade, Livewire, Inertia React, Inertia Vue, and Filament all load `@runtypelabs/persona` 4.39.0. The stack detector reports which app is installed. It does not swap the widget.

Install and wire Laravel AI, Prism, Blade, Livewire, Filament, and Inertia: [docs/installation.md](docs/installation.md). Persona's advanced gallery, and how each demo maps onto Laravel: [docs/advanced-examples.md](docs/advanced-examples.md).

Details: [docs/chat.md](docs/chat.md). Page integration: [docs/integration.md](docs/integration.md). Events: [docs/events.md](docs/events.md). Components and features: [docs/components.md](docs/components.md). Prism and Laravel AI example: [examples/support-chat](examples/support-chat). Decisions: [DECISIONS.md](DECISIONS.md).

## Development

From this repository:

```bash
composer install
composer test
```

`composer test` runs every suite in `phpunit.xml.dist`. The workspace installs `laravel/ai` and `prism-php/prism` as development dependencies so those adapters can be tested. An application does not inherit that.

## Install a package in an application

Composer reads only the root `composer.json` of a Git repository, so requiring this repository does not install `determly/chat` or `determly/chat-laravel`. Require the package directories.

Laravel app, from a sibling checkout:

```bash
composer config repositories.chat '{"type":"path","url":"../determly-ai-chat/packages/chat","options":{"symlink":true}}'
composer config repositories.chat-laravel '{"type":"path","url":"../determly-ai-chat/packages/chat-laravel","options":{"symlink":true}}'
composer require determly/chat-laravel:@dev
```

`determly/chat` is pulled in by `determly/chat-laravel`. A non-Laravel caller requires `determly/chat` alone.

Install `laravel/ai` or `prism-php/prism` in the app when the chat should call a model. With one library installed, it is selected automatically. With both installed, set `DETERMINLY_CHAT_AI` to `laravel-ai` or `prism`. Prism also needs `DETERMINLY_CHAT_PROVIDER` and `DETERMINLY_CHAT_MODEL`. The adapters were checked against Laravel AI 1.4.0 and Prism 0.103.0. Step by step for each library and each front end: [docs/installation.md](docs/installation.md).

## Add a package

1. Create `packages/<name>/composer.json`. Set `name` to `determly/<name>`, set `version`, and point `autoload` at that package's `src/`.
2. Require it from the root `composer.json` as `"determly/<name>": "*@dev"`.
3. Map its test namespace in the root `autoload-dev`.
4. Add a `<testsuite>` whose directory is `packages/<name>/tests`.
5. Run `composer update` from the repository root.
