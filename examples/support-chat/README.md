# Support chat

One tool, two model libraries. The app registers `lookup_order` once. `DETERMLY_CHAT_AI` chooses Laravel AI or Prism. The page script refreshes the orders table and exposes `refresh_orders` to the widget.

This is not a second Laravel application. Copy the tool and the script into the app. The workspace test `SupportChatExampleTest` runs the tool through both responders with fakes, so it does not call a provider. The gallery pages that do call a provider are `/examples/laravel-ai` and `/examples/prism`.

## Install

Require `determly/chat-laravel` as described in the repository README. Then install one library, or both:

```bash
composer require laravel/ai:^1.4
composer require prism-php/prism:^0.103
```

Copy one env file into the app `.env`.

Prism (`examples/support-chat/.env.prism.example`):

```dotenv
DETERMLY_CHAT_AI=prism
DETERMLY_CHAT_PROVIDER=openrouter
DETERMLY_CHAT_MODEL=openai/gpt-4.1-mini
```

Laravel AI (`examples/support-chat/.env.laravel-ai.example`):

```dotenv
DETERMLY_CHAT_AI=laravel-ai
DETERMLY_CHAT_PROVIDER=openrouter
DETERMLY_CHAT_MODEL=openai/gpt-4.1-mini
```

The package defaults are `openrouter` and `openai/gpt-4.1-mini`. If both libraries are installed and `DETERMLY_CHAT_AI` is `auto`, the first message is an error. Set the value. Provider credentials stay in the library's own env vars. OpenRouter uses `OPENROUTER_API_KEY`. This package does not read that key, and `config/determly-chat.php` has no key parameter.

`php artisan determly:chat` prints the selected library.

## Register the tool

Copy `src/LookupOrder.php` into the app as `app/Chat/LookupOrder.php` and set its namespace to `App\Chat`. Then register it in `AppServiceProvider::boot()`:

```php
use App\Chat\LookupOrder;
use Determly\Chat\ToolRegistry;

public function boot(ToolRegistry $tools): void
{
    $tools->register(new LookupOrder, 'app');
}
```

The class in this folder keeps the `Determly\Chat\Examples` namespace so the workspace test can load it. `handle()` returns the order status. It does not call a model and it does not wait on a web task.

The same registration is what Laravel AI and Prism see. You do not write a second tool class for the other library.

## What a turn looks like

The visitor asks "Where is order 1001?".

1. The widget posts the transcript to `POST /determly/chat/dispatch`.
2. The selected library streams a tool call for `lookup_order` with `order_id` `1001`.
3. The runner calls `LookupOrder::handle()` once and appends the result as untrusted data.
4. The library streams the answer. The SSE events are `tool_start`, `tool_complete`, `text_delta`, then `execution_complete`.
5. The page hears `assistant:complete` and refreshes the orders table.

A page tool is the other path. `resources/js/determly-chat.js` registers `refresh_orders` on `document.modelContext`. The widget sends it as a client tool with `origin: webmcp`. The runner pauses with `await`, `awaitReason: local_tool_required`, and `origin: webmcp`. Persona 4.39.0 runs the browser tool and posts `/determly/chat/dispatch/resume`. Read-only tools (`annotations.readOnlyHint`) skip the confirm bubble. Other page tools ask first.

Load the script from your layout. The widget is already injected before `</body>` unless `DETERMLY_CHAT_INJECT=false`.

## A form inside the reply

The widget already registers `DynamicForm`, `ProductCard`, and `Notice`. Ask the model for a contact form and it can reply with only this JSON:

```json
{"text":"Tell me how to reach you.","component":"DynamicForm","props":{"title":"Contact","fields":[{"label":"Email","type":"email","required":true}],"submit_text":"Send"}}
```

Plain answers stay plain text. A longer note uses `emit_artifact_markdown` and opens the side pane. See [docs/components.md](../../docs/components.md).

## Refresh by stack

`assistant:complete` is the same event on every stack. What you call from it is not.

| Stack | Refresh |
|---|---|
| Livewire 3 and Livewire 4 | `Livewire.dispatch('orders-refresh')` and a `#[On('orders-refresh')]` method that reloads the rows. `$wire.$refresh` works from an element inside that component. |
| Filament 4 and Filament 5 | The page is Livewire. Dispatch the same Livewire event. Filament does not get a separate widget. |
| Inertia React and Inertia Vue | `router.reload({ only: ['orders'] })` from `@inertiajs/react` or `@inertiajs/vue3`. |
| Blade | Replace the node yourself, as the example does with `GET /orders`, or reload the document. |

The widget sits on `document.body`. It cannot call `$wire`, a Filament action, or the Inertia router. The page script does that.

`wire:navigate` and a Filament SPA navigation fetch a full document, so the injector adds the widget script again. Livewire component updates send `X-Livewire` and are left alone. Inertia visits after the first page are JSON and are left alone.
