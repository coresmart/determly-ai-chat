# Determly chat

One Persona widget. One SSE dispatch endpoint. Every Laravel stack uses the same pair of packages.

| Package | What it is |
|---|---|
| `determly/chat` | Persona wire in PHP. No Laravel. |
| `determly/chat-laravel` | Dispatch route, widget mount, stack report. |

Widget version: `@runtypelabs/persona` 4.39.0. Docs: https://www.persona-chat.dev. Wire source: https://github.com/runtypelabs/persona/blob/main/packages/persona-wire/src/index.ts

## What an app installs

This repository is a monorepo. Require the package, not the GitHub root. From a sibling checkout:

```bash
composer config repositories.chat '{"type":"path","url":"../determly-ai-chat/packages/chat","options":{"symlink":true}}'
composer config repositories.chat-laravel '{"type":"path","url":"../determly-ai-chat/packages/chat-laravel","options":{"symlink":true}}'
composer require determly/chat-laravel:@dev
```

`determly/chat-laravel` requires `determly/chat`. A non-Laravel caller requires only `determly/chat`.

The service provider registers:

- `POST /determly/chat/dispatch`
- middleware that adds the widget script before `</body>` on full HTML pages
- `<x-determly-chat />` if you turn injection off with `DETERMINLY_CHAT_INJECT=false`
- `php artisan determly:chat` to print the detected stack, the dispatch URL, and registered tools

Detected stacks:

| Installed | Reported stack |
|---|---|
| `filament/filament` | `filament` (wins even if Livewire is installed) |
| `livewire/livewire` | `livewire` |
| `inertiajs/inertia-laravel` and `@inertiajs/react` | `inertia-react` |
| `inertiajs/inertia-laravel` and `@inertiajs/vue3` | `inertia-vue` |
| both Inertia adapters | `inertia` |
| none of the above | `blade` |

The reported stack does not change the widget. It tells you which kind of response the injector has to leave alone.

## What the browser sends

`POST` JSON, the Persona dispatch body:

```json
{
  "messages": [
    { "role": "user", "content": "Find the Laravel 13 release notes" }
  ],
  "context": {},
  "metadata": {}
}
```

`content` may be a string or a list of `{ "type": "text", "text": "..." }` parts. Other roles are ignored.

## What the server streams

`Content-Type: text/event-stream`. Each frame is `event:` plus `data:` JSON with `type`, `executionId`, and `seq`.

A text turn:

1. `execution_start`
2. `turn_start`
3. `text_start`
4. `text_delta` (one or more)
5. `text_complete`
6. `turn_complete` with `stopReason: end_turn`
7. `execution_complete`

A tool call closes any open text block, then sends `tool_start` and `tool_complete`. Arguments travel on `tool_start.parameters`. There is no `origin` field. Quick replies use the built-in `suggest_replies` tool.

Deltas are the text. The server does not send the full answer again at the end.

## Other chats

A chat that is not the Persona widget uses `determly/chat` directly. Flatten the body with `Turn::fromArray()`, then run `Session::run()` and either write SSE or read the frames. Do not invent a second message format. If that chat cannot show tools, it can still show `text_delta` frames.

## Recipes

A recipe registers tools on `Determly\Chat\ToolRegistry`. Tool names are global in the app. Prefix them (`tinyfish_search`). Duplicate names are refused.

A recipe does not render a transcript. The long-running TinyFish rule still holds: a tool starts work and returns a run id. It does not block on `agent->run()`.

Search and fetch do not spend credits. Agent runs do.

## Replace the echo agent

The default agent repeats the last user message so the widget works before an AI package is installed. Bind your own `Determly\Chat\Agent` in a service provider when Laravel AI or Prism is added.
