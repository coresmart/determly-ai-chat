# determly/chat-laravel

Laravel mount for the Determly Persona chat. Depends on `determly/chat`.

One widget for Blade, Livewire, Inertia React, Inertia Vue, and Filament. `POST /determly/chat/dispatch` streams the Persona SSE wire. `php artisan determly:chat` reports the detected stack and which model library is selected.

The package suggests `laravel/ai` 1.4 and `prism-php/prism` 0.103. It does not require them. One installed library is used automatically. Both installed libraries require `DETERMINLY_CHAT_AI`. Neither installed library keeps the echo responder.

This package lives in the [determly-ai-chat](https://github.com/coresmart/determly-ai-chat) monorepo. See the repository README for install.
