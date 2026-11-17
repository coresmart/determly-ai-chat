# Determly chat

One Persona widget. One SSE dispatch endpoint. Every Laravel stack uses the same pair of packages.

Install: [installation.md](installation.md). Advanced examples: [advanced-examples.md](advanced-examples.md). How the page talks to the widget: [integration.md](integration.md). Server frames and widget handle events: [events.md](events.md). Components, artifacts, questions, and layout: [components.md](components.md). A chat wired to both Prism and Laravel AI: [examples/support-chat](../examples/support-chat).

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

A server tool closes any open text block, then sends `tool_start` and `tool_complete`. Arguments travel on `tool_start.parameters`. There is no `origin` field. Quick replies use the built-in `suggest_replies` tool.

A tool that requires approval sends `agent_approval_start` and then `step_await` with `awaitReason: approval_required`. The stream stops there. The widget posts the decision to `{dispatch url}/v1/agents/{agentId}/approve` with `executionId`, `approvalId`, `decision` (`approved` or `denied`), and optional `remember`. The response is another SSE stream.

A page tool (`clientTools` on the dispatch body, or a name starting with `webmcp:`) sends `step_await` with `awaitReason: local_tool_required`. A tool the page sent with `origin: webmcp` includes that `origin` on the pause so Persona 4.39.0 runs it in the browser. The description and `parametersSchema` from that object are what the model sees. A bare name gets a generic `input` string. The widget posts `{dispatch url}/resume` with `executionId` and `toolOutputs` keyed by `toolCallId`. That request does not include the transcript, so the server keeps the execution until the turn finishes. The field list is in [events.md](events.md).

Deltas are the text. The server does not send the full answer again at the end. The default responder streams the echo word by word, then offers two suggestions.

## Other chats

A chat that is not the Persona widget uses `determly/chat` directly. Flatten the body with `Turn::fromArray()`, then run `Session::run()` and either write SSE or read the frames. Do not invent a second message format. If that chat cannot show tools, it can still show `text_delta` frames.

## Recipes

A recipe registers tools on `Determly\Chat\ToolRegistry`. Tool names are global in the app. Prefix them (`tinyfish_search`). Duplicate names are refused.

A recipe does not render a transcript. The long-running TinyFish rule still holds: a tool starts work and returns a run id. It does not block on `agent->run()`.

Search and fetch do not spend credits. Agent runs do.

## Model

`DETERMINLY_CHAT_AI` chooses who answers.

| Value | When it is used |
|---|---|
| `auto` | Echo when neither library is installed. Laravel AI when only `laravel/ai` is installed. Prism when only `prism-php/prism` is installed. If both are installed, the first message reports that this value must be set. |
| `echo` | The echo responder, whether or not a library is installed. |
| `laravel-ai` | Laravel AI. `DETERMINLY_CHAT_PROVIDER` and `DETERMINLY_CHAT_MODEL` are optional and fall back to the Laravel AI defaults. |
| `prism` | Prism. Set both `DETERMINLY_CHAT_PROVIDER` and `DETERMINLY_CHAT_MODEL`. |

The adapters were written against `laravel/ai` 1.4.0 and `prism-php/prism` 0.103.0. `determly/chat-laravel` does not require either library. Install the one the app should use. The workspace installs both so the adapters can be tested.

`DETERMINLY_CHAT_INSTRUCTIONS` replaces the default system text. Tool results go back to the model as untrusted data, not instructions. Search and fetch do not spend credits. A tool that starts a web task returns a run id. It does not call `agent->run()`.

Laravel AI and Prism propose text, reasoning, and tool calls. The runner executes server tools, pauses for approval, and pauses for a page tool. Each library takes one model step per call, so it does not execute the tool itself. Prism's tool callback returns an empty string for the same reason. After a tool, the runner calls the responder again with the tool result in the transcript. `php artisan determly:chat` prints the choice.

You can still bind your own `Determly\Chat\Run\Responder`. Return `Step::text()`, `Step::reasoning()`, `Step::tool()`, and `Step::suggestions()`. Do not call the model again yourself after a tool. The runner does that.

The support chat example registers one tool and runs it through both libraries. Copy `examples/support-chat/.env.prism.example` or `examples/support-chat/.env.laravel-ai.example`. The page script is `examples/support-chat/resources/js/determly-chat.js`.
