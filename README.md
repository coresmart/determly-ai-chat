# determly-ai-chat

Determly monorepo for [Persona](https://www.persona-chat.dev) chat. Each installable library lives in `packages/` with its own `composer.json`. The root project is a private Composer workspace that links those packages with a path repository, the same layout as `coresmart/tinyfish-php`.

| Package | Path | Role |
|---|---|---|
| [`determly/chat`](packages/chat) | `packages/chat` | Persona SSE wire. No Laravel dependency. Other chats speak this. |
| [`determly/chat-laravel`](packages/chat-laravel) | `packages/chat-laravel` | Dispatch route and the one widget mount for Blade, Livewire, Inertia, and Filament. |

`determly/chat-laravel` depends on `determly/chat`. Blade, Livewire, Inertia React, Inertia Vue, and Filament all load `@runtypelabs/persona` 4.39.0. The stack detector reports which app is installed. It does not swap the widget.

Install and wire Laravel AI, Prism, Blade, Livewire, Filament, and Inertia: [docs/installation.md](docs/installation.md). The runnable Persona examples are the Laravel app in [examples/gallery](examples/gallery). How each demo maps onto Laravel: [docs/advanced-examples.md](docs/advanced-examples.md).

Details: [docs/chat.md](docs/chat.md). Page integration: [docs/integration.md](docs/integration.md). Events: [docs/events.md](docs/events.md). Components and features: [docs/components.md](docs/components.md). Prism and Laravel AI example: [examples/support-chat](examples/support-chat). Decisions: [DECISIONS.md](DECISIONS.md).

## Gallery

`examples/gallery` is a small Laravel app on the monorepo `vendor/`. The scripted pages answer in echo mode and do not need an API key. `/examples/laravel-ai` and `/examples/prism` send the sentence to Laravel AI or Prism. Those two need `DETERMLY_CHAT_PROVIDER`, `DETERMLY_CHAT_MODEL`, and the provider key. Copy `examples/gallery/.env.example` to `examples/gallery/.env`. The example sets `DETERMLY_CHAT_AI=echo` and `DETERMLY_CHAT_INJECT=false`. `.env` is not committed. Approvals and resumes use the file cache.

From this repository:

```bash
composer install
cp -n examples/gallery/.env.example examples/gallery/.env
php -S 127.0.0.1:8080 -t examples/gallery/public examples/gallery/public/index.php
```

Open http://127.0.0.1:8080/. The router script is required so `/examples/...` is handled by Laravel. Pages and the `APP_KEY` are listed in [examples/gallery/README.md](examples/gallery/README.md).

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

Install `laravel/ai` or `prism-php/prism` in the app when the chat should call a model. With one library installed, it is selected automatically. With both installed, set `DETERMLY_CHAT_AI` to `laravel-ai` or `prism`. Prism also needs `DETERMLY_CHAT_PROVIDER` and `DETERMLY_CHAT_MODEL`. The adapters were checked against Laravel AI 1.4.0 and Prism 0.103.0. Step by step for each library and each front end: [docs/installation.md](docs/installation.md).

## Add a package

1. Create `packages/<name>/composer.json`. Set `name` to `determly/<name>`, set `version`, and point `autoload` at that package's `src/`.
2. Require it from the root `composer.json` as `"determly/<name>": "*@dev"`.
3. Map its test namespace in the root `autoload-dev`.
4. Add a `<testsuite>` whose directory is `packages/<name>/tests`.
5. Run `composer update` from the repository root.
