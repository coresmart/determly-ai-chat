# determly/chat-laravel

Laravel mount for the Determly Persona chat. Depends on `determly/chat`.

One widget for Blade, Livewire, Inertia React, Inertia Vue, and Filament. `POST /determly/chat/dispatch` streams the Persona SSE wire. `php artisan determly:chat` reports the detected stack and which model library is selected.

The package suggests `laravel/ai` 1.4 and `prism-php/prism` 0.103. It does not require them. One installed library is used automatically. Both installed libraries require `DETERMLY_CHAT_AI`. Neither installed library keeps the echo responder.

`ToolEquivalents::laravel()` and `ToolEquivalents::prism()` turn a registered tool into a Laravel AI tool and a Prism tool that call the same `handle()`. The Persona responders keep a separate copy that only advertises the schema.

This package lives in the [determly-ai-chat](https://github.com/coresmart/determly-ai-chat) monorepo. Installation for Laravel AI, Prism, and each front end is `docs/installation.md`. Integration and events are in `docs/integration.md` and `docs/events.md`. Components, artifacts, questions, layout, and the theme are in `docs/components.md` and `docs/installation.md`. The Prism and Laravel AI example is `examples/support-chat`.
