# Gallery

A Laravel app you can open in the browser. Each page mounts the Persona 4.39.0 widget and posts to `/examples/{slug}/dispatch`. The scripted pages use `GalleryResponder`. `DETERMLY_CHAT_AI=echo` and `DETERMLY_CHAT_INJECT=false`. `/examples/laravel-ai` and `/examples/prism` do not use that script. They send the sentence to Laravel AI or Prism. The example uses OpenRouter (`openrouter` / `openai/gpt-4.1-mini`). The provider key is `OPENROUTER_API_KEY` in `.env`. It is not a `determly-chat` config parameter.

The app has no `vendor/` of its own. `public/index.php` loads the monorepo `vendor/autoload.php` and sets `COMPOSER_VENDOR_DIR` so package discovery can see it. `Determly\Chat\Laravel\ChatServiceProvider` is registered from `bootstrap/providers.php`. The gallery provider then binds `GalleryResponder` and registers `query_orders`, `refund_order`, and `lookup_order`.

The cache driver is `file`. PHP's built-in server starts a new process for every request, so an array cache would lose the paused execution and approval or resume would fail. `storage/framework/cache` is created on boot if it is missing. It must be writable. Session storage is a file too.

Copy `examples/gallery/.env.example` to `examples/gallery/.env`. The example has a local `APP_KEY`, `CACHE_STORE=file`, and `SESSION_DRIVER=file`. That key only encrypts this app's cookies. It is not an API key. `.env` is not committed. Put the provider key there.

## Start

From the repository root:

```bash
composer install
cp -n examples/gallery/.env.example examples/gallery/.env
php -S 127.0.0.1:8080 -t examples/gallery/public examples/gallery/public/index.php
```

The last path is the router. `php -S -t examples/gallery/public` alone serves existing files and 404s `/examples/...`.

Open http://127.0.0.1:8080/.

The panel opens on load (`launcher.autoExpand`) and does not keep the transcript (`persistState` is off) so the starter chips are there each time. `autoFocusInput` stays off.

## Pages

| Page | URL | What a starter does |
|---|---|---|
| Home | http://127.0.0.1:8080/ | Lists the pages. |
| Talk to Your Data | http://127.0.0.1:8080/examples/data | `query_orders`, then a markdown artifact. "Filter the table to shipped orders" runs the read-only page tool `filter_orders`, which reloads `GET /examples/data/table`. |
| Ask User Question | http://127.0.0.1:8080/examples/ask | Pauses on `ask_user_question`. After you answer, chips from `suggest_replies`. |
| Artifact Sidebar | http://127.0.0.1:8080/examples/artifacts | `emit_artifact_markdown` and a ProductCard via `emit_artifact_component`. |
| Dynamic Components | http://127.0.0.1:8080/examples/components | A JSON-only reply for DynamicForm, ProductCard, or Notice. |
| Agent Loop | http://127.0.0.1:8080/examples/agent | Scripted reasoning, `lookup_order`, then a sentence written in PHP. |
| Laravel AI | http://127.0.0.1:8080/examples/laravel-ai | The sentence goes to Laravel AI. The model chooses the tool and writes the answer. |
| Prism | http://127.0.0.1:8080/examples/prism | The sentence goes to Prism. The model chooses the tool and writes the answer. |
| Tool Approval | http://127.0.0.1:8080/examples/approval | `refund_order` pauses. `handle()` runs after you approve, and the list reloads from `GET /examples/approval/refunds`. |
| Tool Action Handlers | http://127.0.0.1:8080/examples/actions | `add_to_cart` asks first, then adds a line to the cart on the page. |
| Feedback Events | http://127.0.0.1:8080/examples/feedback | Upvote, downvote, and copy post to `/examples/feedback/feedback` and `/examples/feedback/copied`. |
| Programmatic Input Focus | http://127.0.0.1:8080/examples/focus | Buttons call `determlyChat.focusInput()` and dispatch `persona:focusInput`. |
| Form Field Reference | http://127.0.0.1:8080/examples/forms | One DynamicForm with every field type, both widths, required, and helper text. |
| Event Inspector | http://127.0.0.1:8080/examples/events | A live log of persona handle events. |
| Shadow-aware Page Context | http://127.0.0.1:8080/examples/shadow | `read_product` walks the product card inside the shadow root. |
| Streaming Markdown Tables | http://127.0.0.1:8080/examples/tables | The orders table streams as text deltas. |
| Tool and Reasoning Loaders | http://127.0.0.1:8080/examples/loaders | A reasoning row, a short pause, then `query_orders`. |
| Theme | http://127.0.0.1:8080/examples/theme | A PHP `Theme`: teal accent, white surface, serif font, large radius. |

Voice, bring-your-own voice, server TTS, text-reveal animations, custom loader graphics, and file attachments are named on the home page and are not routes.

Dispatch, resume, and approve skip CSRF, matching the chat package. The path is `/examples/{slug}/dispatch` with no query string, because Persona appends `/resume` and `/v1/agents/{agent}/approve`. Feedback posts use the page's CSRF token.
