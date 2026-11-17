# Advanced examples in Laravel

[Persona's advanced gallery](https://www.persona-chat.dev/advanced.html) is a set of demos on top of `@runtypelabs/persona` 4.39.0. The runnable copy in this repo is the Laravel app at [examples/gallery](../examples/gallery). Open it in the browser. Each page mounts the Persona widget and dispatches to its own route. The scripted responder does not call a model. `/examples/laravel-ai` and `/examples/prism` do.

From the repository root, after `composer install`:

```bash
cp -n examples/gallery/.env.example examples/gallery/.env
php -S 127.0.0.1:8080 -t examples/gallery/public examples/gallery/public/index.php
```

The last argument is the router. Without it, the built-in server only serves files that exist under `public/` and `/examples/...` 404s. Then open [http://127.0.0.1:8080/](http://127.0.0.1:8080/).

Copy `examples/gallery/.env.example` to `examples/gallery/.env`. The example sets `DETERMLY_CHAT_AI=echo`, `DETERMLY_CHAT_PROVIDER`, `DETERMLY_CHAT_MODEL`, `DETERMLY_CHAT_INJECT=false`, `CACHE_STORE=file`, `SESSION_DRIVER=file`, and a local `APP_KEY`. `.env` is not committed. Put the provider key in `.env`. The gallery forces echo for the scripted pages. Both `laravel/ai` and `prism-php/prism` are installed in this workspace, so `auto` would error. The two model pages ignore that echo setting and call the library named by the route. They need the provider key in the environment. Echo mode needs no API key. The file cache is required because PHP's built-in server is a new process per request; the array cache would drop an approval or a resume. `storage/framework/cache` must be writable. The app has no `vendor/` of its own. `public/index.php` points `COMPOSER_VENDOR_DIR` at the monorepo `vendor/` and loads that autoload file.

How to open each page is in [examples/gallery/README.md](../examples/gallery/README.md). Install and the per-stack refresh calls are in [installation.md](installation.md). Component props are in [components.md](components.md). Frames and handle events are in [events.md](events.md).

Voice input and output, bring-your-own voice, server TTS, text-reveal animations, custom loader graphics, and file attachments are not pages in this gallery. The home page names them once.

| Example | Persona group | Gallery |
|---|---|---|
| [Talk to Your Data](https://www.persona-chat.dev/analytics-agent-demo.html) | Generative UX | [/examples/data](../examples/gallery/resources/views/examples/data.blade.php) |
| [Ask User Question and Suggested Replies](https://www.persona-chat.dev/ask-user-question-demo.html) | Generative UX | [/examples/ask](../examples/gallery/resources/views/examples/ask.blade.php) |
| [Artifact Sidebar](https://www.persona-chat.dev/artifact-demo.html) | Generative UX | [/examples/artifacts](../examples/gallery/resources/views/examples/artifacts.blade.php) |
| [Dynamic Components](https://www.persona-chat.dev/dynamic-components.html) | Generative UX | [/examples/components](../examples/gallery/resources/views/examples/components.blade.php) |
| [Voice Input and Output](https://www.persona-chat.dev/voice-integration-demo.html) | Generative UX | Not in this gallery. Needs a speech service. |
| [Bring-Your-Own Voice](https://www.persona-chat.dev/custom-voice-provider-demo.html) | Generative UX | Not in this gallery. Needs a speech service. |
| [Server TTS](https://www.persona-chat.dev/server-tts-demo.html) | Generative UX | Not in this gallery. Needs a speech service. |
| [Agent Loop](https://www.persona-chat.dev/agent-demo.html) | Generative UX, hidden in Persona's gallery | [/examples/agent](../examples/gallery/resources/views/examples/agent.blade.php) |
| Laravel AI | A model, not a Persona demo | [/examples/laravel-ai](../examples/gallery/resources/views/examples/model.blade.php) |
| Prism | A model, not a Persona demo | [/examples/prism](../examples/gallery/resources/views/examples/model.blade.php) |
| [Tool and Reasoning Loaders](https://www.persona-chat.dev/tool-loading-demo.html) | Patterns | [/examples/loaders](../examples/gallery/resources/views/examples/loaders.blade.php) |
| [Text Reveal Effects](https://www.persona-chat.dev/stream-animations-demo.html) | Patterns | Not in this gallery. Text arrives as `text_delta` with no reveal animation. |
| [Streaming Markdown Tables](https://www.persona-chat.dev/streaming-table-demo.html) | Patterns | [/examples/tables](../examples/gallery/resources/views/examples/tables.blade.php) |
| [Replace the Loader](https://www.persona-chat.dev/custom-loading-indicator.html) | Patterns | Not in this gallery. The stock script keeps Persona's loader. |
| [Tool Approval](https://www.persona-chat.dev/approval-demo.html) | Patterns | [/examples/approval](../examples/gallery/resources/views/examples/approval.blade.php) |
| [Tool Action Handlers](https://www.persona-chat.dev/action-middleware.html) | Patterns | [/examples/actions](../examples/gallery/resources/views/examples/actions.blade.php) |
| [File Attachments](https://www.persona-chat.dev/attachments-demo.html) | Patterns | Not in this gallery. |
| [Feedback Events](https://www.persona-chat.dev/feedback-integration-demo.html) | Patterns | [/examples/feedback](../examples/gallery/resources/views/examples/feedback.blade.php) |
| [Programmatic Input Focus](https://www.persona-chat.dev/focus-input-demo.html) | Patterns | [/examples/focus](../examples/gallery/resources/views/examples/focus.blade.php) |
| [Form Field Reference](https://www.persona-chat.dev/dynamic-form-fields.html) | Reference | [/examples/forms](../examples/gallery/resources/views/examples/forms.blade.php) |
| [Event Inspector](https://www.persona-chat.dev/event-stream-testing.html) | Reference | [/examples/events](../examples/gallery/resources/views/examples/events.blade.php) |
| [Shadow-aware Page Context](https://www.persona-chat.dev/smart-dom-reader-demo.html) | Reference | [/examples/shadow](../examples/gallery/resources/views/examples/shadow.blade.php) |
| [Scroll Engineering](https://www.persona-chat.dev/scroll-engineering.html) | Reference | Persona's default scroll. Not a separate gallery page. |

## Generative UX

### Talk to Your Data

[/examples/data](../examples/gallery/resources/views/examples/data.blade.php) shows an orders table. The starter calls the server tool `query_orders`, then `emit_artifact_markdown` with a markdown table of those rows. `filter_orders` is a page tool with `annotations.readOnlyHint`, so the confirm bubble is skipped. Its `execute` loads `GET /examples/data/table` and replaces the table.

The same shape in an app is one server tool that returns the rows, an artifact when the answer should leave the thread, and a page tool when the visitor's screen has to change. The package does not include a SQL engine or a chart component. [installation.md](installation.md) shows the Livewire, Filament, Inertia, and Blade refresh calls.

### Ask User Question and Suggested Replies

[/examples/ask](../examples/gallery/resources/views/examples/ask.blade.php) calls `ask_user_question`. The widget advertises that tool because `DETERMLY_CHAT_ASK` is on, so the runner pauses with `step_await` and `awaitReason: local_tool_required`. The sheet posts `{apiUrl}/resume`. The next step is `suggest_replies`, which the runner emits through `Step::suggestions`.

`DETERMLY_CHAT_ASK=false` stops advertising the question tool. The gallery's starter chips are the ones on that page. The stock widget's starters stay "What can you do?" and "What tools are available?" when a page does not replace them.

### Artifact Sidebar

[/examples/artifacts](../examples/gallery/resources/views/examples/artifacts.blade.php) calls both artifact tools in one turn:

- `emit_artifact_markdown` with `title` and `content`
- `emit_artifact_component` with `title`, `component` `ProductCard`, and `props` as a JSON object string

`DETERMLY_CHAT_ARTIFACTS` defaults to on, for `markdown` and `component`. The runner emits `artifact_start`, then `artifact_delta` or `artifact_update`, then `artifact_complete`. `DynamicForm`, `ProductCard`, and `Notice` are valid `component` names. A component the page added on `window.DetermlyChatComponents` before the widget script is valid too. On a narrow screen the pane is a drawer.

### Dynamic Components

[/examples/components](../examples/gallery/resources/views/examples/components.blade.php) replies with only a JSON object. One starter for each of `DynamicForm`, `ProductCard`, and `Notice`.

```json
{"text":"Tell me how to reach you.","component":"DynamicForm","props":{"title":"Contact","submit_text":"Send","fields":[{"label":"Email","type":"email","required":true}]}}
```

The stream parser shows `text` and mounts the component. A form submit and the product button call `sendMessage` on `window.determlyChat`. Props are listed in [components.md](components.md).

### Voice, bring-your-own voice, and server TTS

Those three demos attach a speech engine to the composer. They are not in this gallery. The stock widget does not set `voiceRecognition` or a speech provider.

### Agent Loop

[/examples/agent](../examples/gallery/resources/views/examples/agent.blade.php) is the multi-step turn: reasoning, the server tool `lookup_order`, then the answer. The order id comes from the message. A number that is not in the table is reported as missing. Persona hides this demo in its own gallery. It is the path `POST /examples/agent/dispatch` runs here, and the path `POST /determly/chat/dispatch` runs in an app.

The library proposes one step. The runner executes a server tool or pauses. It then calls the library again, up to eight rounds. On this page the library is the scripted responder. Reasoning is `reasoning_start`, `reasoning_delta`, and `reasoning_complete`. Tools are `tool_start` and `tool_complete`.

### Laravel AI and Prism

[/examples/laravel-ai](../examples/gallery/resources/views/examples/model.blade.php) and [/examples/prism](../examples/gallery/resources/views/examples/model.blade.php) send the sentence to a model. `ModelChat` builds `LaravelAiResponder` or `PrismResponder` with `lookup_order` and `query_orders`. The model sees the words. It chooses the tool. The runner calls `handle()`, then calls the model again with the tool result. The answer text is the model's, not a PHP template.

The starters ask about a customer ("Did Jordan Blake's order ship?"). The scripted agent page cannot answer that. It only keeps a number from the message.

Set `DETERMLY_CHAT_PROVIDER`, `DETERMLY_CHAT_MODEL`, and the provider key. For `openai` / `gpt-4o-mini`, the key is `OPENAI_API_KEY`. A missing key is an `execution_error` on the stream. The page does not fall back to the script.

## Patterns

### Tool and Reasoning Loaders

[/examples/loaders](../examples/gallery/resources/views/examples/loaders.blade.php) yields a reasoning row, pauses long enough to see it, then calls `query_orders`.

The stock widget sets `features.showReasoning` and `features.showToolCalls` to true. It does not set a custom loader animation. The graphic is the one Persona 4.39.0 uses when those flags are on.

### Text Reveal Effects

Not in this gallery. The demo cycles `features.streamAnimation.type`. The stock widget does not set `features.streamAnimation`. Deltas still stream. They appear without a reveal animation.

### Streaming Markdown Tables

[/examples/tables](../examples/gallery/resources/views/examples/tables.blade.php) streams a markdown table as separate `text_delta` frames.

The 4.39.0 installer sets `postprocessMessage` to `markdownPostprocessor` when the page did not set its own. A markdown table in `text_delta` or in `emit_artifact_markdown` content goes through that renderer. There is no extra table setting in `config/determly-chat.php`.

### Replace the Loader

Not in this gallery. The stock script does not pass a loading renderer. The launcher and the transcript use Persona's loader.

### Tool Approval

[/examples/approval](../examples/gallery/resources/views/examples/approval.blade.php) calls `refund_order`. `requiresApproval()` is true, so the runner emits `agent_approval_start` and `step_await` with `awaitReason: approval_required`, and does not call `handle()` yet. The widget posts to `/examples/approval/dispatch/v1/agents/determly/approve` with `executionId`, `approvalId`, and `decision` (`approved` or `denied`). After approval, `handle()` records the refund and the page reloads `GET /examples/approval/refunds`.

In an app the approve URL is `{dispatch}/v1/agents/{agent}/approve`. `remember: true` stores that tool name in the cache until `determly-chat:allowed-tools` is cleared. Denied skips `handle()`. The gallery uses the file cache so the paused execution survives the next PHP process.

### Tool Action Handlers

[/examples/actions](../examples/gallery/resources/views/examples/actions.blade.php) calls the page tool `add_to_cart`. It is not read-only, so the confirm bubble shows. `execute` appends a line to the cart on the page, then posts `/resume`.

This package does not register an action parser. A read-only tool sets `annotations.readOnlyHint` so the confirm bubble is skipped. The same `execute` function is where Livewire, Filament, Inertia, and Blade differ. Use the `refreshOrders` body from [installation.md](installation.md) when the page should reload server HTML, as `/examples/data` does.

### File Attachments

Not in this gallery. The stock widget does not enable that composer. There is no upload route in this package.

### Feedback Events

[/examples/feedback](../examples/gallery/resources/views/examples/feedback.blade.php) listens for `message:feedback` and `message:copy` and posts them to Laravel. The page shows the stored rows. Upvote and downvote are turned on for that page (`messageActions.showUpvote` and `showDownvote`). Copy is already on.

```js
window.addEventListener('persona:chat-ready', function (event) {
  var handle = event.detail;
  handle.on('message:feedback', function (feedback) {
    fetch('/examples/feedback/feedback', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/html',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ type: feedback.type, messageId: feedback.messageId })
    });
  });
  handle.on('message:copy', function (message) {
    fetch('/examples/feedback/copied', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/html',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ id: message.id })
    });
  });
});
```

Those two URLs belong to the gallery. An app uses its own routes, behind the same auth as the rest of the app. The chat dispatch routes are not these URLs. The chat package does not store feedback.

### Programmatic Input Focus

[/examples/focus](../examples/gallery/resources/views/examples/focus.blade.php) has two buttons:

- `window.determlyChat.focusInput()` focuses the open composer. It fails when the panel is closed.
- `window.dispatchEvent(new CustomEvent('persona:focusInput'))` focuses every instance on the page.

`autoFocusInput` stays off. That is Persona's default, and the stock widget does not set it. The gallery does open the panel with `launcher.autoExpand` so the composer is there to focus. The handle is `event.detail` and `window.determlyChat`.

## Reference

### Form Field Reference

[/examples/forms](../examples/gallery/resources/views/examples/forms.blade.php) sends one `DynamicForm` with `text`, `email`, `tel`, `url`, `number`, `date`, `time`, and `textarea`. `width` is `full` or `half`. `required` draws the asterisk. `helper_text` is the line under the control.

That is what `DynamicForm` implements. Persona's demo also shows input masks. This component does not. A masked field is a component you register yourself.

Half fields sit two to a row. A submit posts a sentence to the chat through `sendMessage`, one `name: value` pair per field.

### Event Inspector

[/examples/events](../examples/gallery/resources/views/examples/events.blade.php) appends persona handle events to a list on the page.

The stock widget does not set `features.showEventStreamToggle`. Subscribe instead. The names and payloads are in [events.md](events.md). `assistant:complete` and `run:finished` are the ones a Laravel page uses to refresh. `approval:requested` and `approval:resolved` match the approval tool. `stream:paused` is a dropped stream, which is separate from `step_await`.

### Shadow-aware Page Context

[/examples/shadow](../examples/gallery/resources/views/examples/shadow.blade.php) puts a product card in a shadow root. The page tool `read_product` is read-only. Its `execute` walks `shadowRoot` and returns the title, price, and sku.

The injected widget does not turn on a shadow reader, and it mounts with shadow DOM off. The page tool runs in the page, so it can walk a shadow root the page created.

### Scroll Engineering

The demo tunes `features.scrollBehavior` so a streaming thread stays pinned to the latest line without fighting the visitor.

The stock widget does not set `features.scrollBehavior`. Persona 4.39.0's own defaults apply. There is no env key for those options, and this gallery does not add a page for it.

## What stays off

`config/determly-chat.php` covers artifacts, questions, WebMCP, and the floating, docked, and fullscreen mounts. Speech, file uploads, a custom loader, and a reveal animation are not flags on that file. They stay off so a new install matches the tested widget, and they are not pages in `examples/gallery`.
