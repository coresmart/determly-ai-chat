# Advanced examples in Laravel

[Persona's advanced gallery](https://www.persona-chat.dev/advanced.html) is a set of demos on top of `@runtypelabs/persona` 4.39.0. Determly does not ship a second copy of those demos. One Laravel widget plays the same roles: a floating launcher, a dispatch route, and the hooks below.

The gallery has three groups. Agent Loop is in the catalog and hidden from the gallery. It is the turn our runner already executes.

Install and the per-stack refresh calls are in [installation.md](installation.md). Component props are in [components.md](components.md). Frames and handle events are in [events.md](events.md).

| Example | Persona group | In this package |
|---|---|---|
| [Talk to Your Data](https://www.persona-chat.dev/analytics-agent-demo.html) | Generative UX | You build it from a server tool plus an artifact. |
| [Ask User Question and Suggested Replies](https://www.persona-chat.dev/ask-user-question-demo.html) | Generative UX | On. `DETERMLY_CHAT_ASK` and follow-up chips. |
| [Artifact Sidebar](https://www.persona-chat.dev/artifact-demo.html) | Generative UX | On. `emit_artifact_markdown` and `emit_artifact_component`. |
| [Dynamic Components](https://www.persona-chat.dev/dynamic-components.html) | Generative UX | On. `DynamicForm`, `ProductCard`, and `Notice`. |
| [Voice Input and Output](https://www.persona-chat.dev/voice-integration-demo.html) | Generative UX | Not enabled. Needs a speech service. |
| [Bring-Your-Own Voice](https://www.persona-chat.dev/custom-voice-provider-demo.html) | Generative UX | Not enabled. Needs a speech service. |
| [Server TTS](https://www.persona-chat.dev/server-tts-demo.html) | Generative UX | Not enabled. Needs a speech service. |
| [Agent Loop](https://www.persona-chat.dev/agent-demo.html) | Generative UX, hidden in the gallery | On. The dispatch runner. |
| [Tool and Reasoning Loaders](https://www.persona-chat.dev/tool-loading-demo.html) | Patterns | Reasoning and tool rows are on. The loader graphic is Persona's default. |
| [Text Reveal Effects](https://www.persona-chat.dev/stream-animations-demo.html) | Patterns | Not enabled. Text arrives as `text_delta` with no reveal animation. |
| [Streaming Markdown Tables](https://www.persona-chat.dev/streaming-table-demo.html) | Patterns | On, through the 4.39.0 markdown renderer the installer attaches. |
| [Replace the Loader](https://www.persona-chat.dev/custom-loading-indicator.html) | Patterns | Not enabled. The stock script keeps Persona's loader. |
| [Tool Approval](https://www.persona-chat.dev/approval-demo.html) | Patterns | On. `requiresApproval()` on a tool. |
| [Tool Action Handlers](https://www.persona-chat.dev/action-middleware.html) | Patterns | Use a page tool. The package does not install an action parser. |
| [File Attachments](https://www.persona-chat.dev/attachments-demo.html) | Patterns | Not enabled. |
| [Feedback Events](https://www.persona-chat.dev/feedback-integration-demo.html) | Patterns | Listen on the handle and post to your own route. |
| [Programmatic Input Focus](https://www.persona-chat.dev/focus-input-demo.html) | Patterns | The handle supports it. The stock config leaves auto focus off. |
| [Form Field Reference](https://www.persona-chat.dev/dynamic-form-fields.html) | Reference | `DynamicForm` covers the listed field types, width, required, and helper text. It does not mask input. |
| [Event Inspector](https://www.persona-chat.dev/event-stream-testing.html) | Reference | Subscribe on `persona:chat-ready`. The stock widget does not turn on Persona's inspector toggle. |
| [Shadow-aware Page Context](https://www.persona-chat.dev/smart-dom-reader-demo.html) | Reference | Not enabled. Read the page from a page tool you write. |
| [Scroll Engineering](https://www.persona-chat.dev/scroll-engineering.html) | Reference | Persona's default scroll. The package does not set `features.scrollBehavior`. |

## Generative UX

### Talk to Your Data

The demo keeps a table in the browser, exposes it as WebMCP tools, and draws charts in the artifact pane.

In Laravel the rows live in the database. Register one tool that runs the query and returns the rows. Ask the model to call `emit_artifact_markdown` with a markdown table when the answer is long enough to leave the thread. A filter that must change the page the visitor is looking at is a page tool named for that screen, such as `refresh_orders`, with `refreshOrders()` from [installation.md](installation.md). The package does not include a SQL engine or a chart component.

### Ask User Question and Suggested Replies

The demo has two tools. `ask_user_question` blocks until the visitor picks. `suggest_replies` shows chips and continues.

Both are advertised by the injected widget. `DETERMLY_CHAT_ASK=false` stops the question tool. Follow-up chips stay available. Starter chips are "What can you do?" and "What tools are available?". The model instructions already mention both tools. Echo mode emits two suggestions on its own and does not call a model.

The question pause is `step_await` with `awaitReason: local_tool_required`. The widget shows the sheet and posts `/determly/chat/dispatch/resume`.

### Artifact Sidebar

The demo streams markdown and registered components into a resizable pane. On a narrow screen the pane is a drawer.

`DETERMLY_CHAT_ARTIFACTS` defaults to on, for `markdown` and `component`. The model calls:

- `emit_artifact_markdown` with `title` and `content`
- `emit_artifact_component` with `title`, `component`, and `props` as a JSON object string

The runner emits `artifact_start`, then `artifact_delta` or `artifact_update`, then `artifact_complete`. `DynamicForm`, `ProductCard`, and `Notice` are valid `component` names. A component the page added on `window.DetermlyChatComponents` before the widget script is valid too.

### Dynamic Components

The demo renders a component from JSON in the assistant message.

Reply with plain text, or with one JSON object and no other text:

```json
{"text":"Tell me how to reach you.","component":"DynamicForm","props":{"title":"Contact","submit_text":"Send","fields":[{"label":"Email","type":"email","required":true}]}}
```

`component` is `DynamicForm`, `ProductCard`, or `Notice`. The stream parser shows `text` and mounts the component. A form submit and the product button call `sendMessage` on `window.determlyChat`. Props are listed in [components.md](components.md).

Add another component by assigning it on `window.DetermlyChatComponents` in a script that runs before the injected widget. The installer copies that object onto `config.components`.

### Voice, bring-your-own voice, and server TTS

Those three demos attach a speech engine to the composer. The stock widget does not set `voiceRecognition` or a speech provider. Turning them on needs a speech service and a widget config this package does not build. Leave them off until that service exists.

### Agent Loop

The demo is a multi-step turn: text, reasoning, tools, then a final answer. Persona hides it in the gallery. It is the default path here.

`POST /determly/chat/dispatch` runs that loop for Laravel AI, Prism, or echo. The library proposes one step. The runner executes a server tool or pauses. It then calls the library again, up to eight rounds. Reasoning is `reasoning_start`, `reasoning_delta`, and `reasoning_complete`. Tools are `tool_start` and `tool_complete`. Search and fetch do not spend credits. A tool that starts a web task returns a run id and returns.

## Patterns

### Tool and Reasoning Loaders

The demo changes how a tool call and a reasoning block look while they stream.

The stock widget sets `features.showReasoning` and `features.showToolCalls` to true, so those rows are visible. It does not set a custom loader animation. The graphic is the one Persona 4.39.0 uses when those flags are on.

### Text Reveal Effects

The demo cycles `features.streamAnimation.type` through typewriter, letter rise, word fade, and the other built-in reveals. The default in Persona is `none`.

The stock widget does not set `features.streamAnimation`. Deltas still stream. They appear without a reveal animation.

### Streaming Markdown Tables

The demo shows a markdown table that keeps its column widths while rows are still arriving.

The 4.39.0 installer sets `postprocessMessage` to `markdownPostprocessor` when the page did not set its own. A markdown table in `text_delta` or in `emit_artifact_markdown` content goes through that renderer. There is no extra table setting in `config/determly-chat.php`.

### Replace the Loader

The demo swaps the waiting indicator for a custom drawing. The stock script does not pass a loading renderer. The launcher and the transcript use Persona's loader.

### Tool Approval

The demo asks before a tool runs.

```php
public function requiresApproval(): bool
{
    return true;
}
```

The runner emits `agent_approval_start` and `step_await` with `awaitReason: approval_required`, and does not call `handle()` yet. The widget posts to `/determly/chat/dispatch/v1/agents/determly/approve` with `executionId`, `approvalId`, and `decision` (`approved` or `denied`). `remember: true` stores that tool name in the cache until `determly-chat:allowed-tools` is cleared. Denied skips `handle()`.

### Tool Action Handlers

The demo parses an action out of the answer and runs it against the live DOM, such as navigation or a cart.

This package does not register an action parser. Put the action in a page tool. The model calls `refresh_orders` or `add_to_cart`. The server pauses with `origin: webmcp`. The browser function you registered on `document.modelContext` runs, then posts `/resume`. A read-only tool sets `annotations.readOnlyHint` so the confirm bubble is skipped. Anything else asks first.

The same `execute` function is where Livewire, Filament, Inertia, and Blade differ. Use the `refreshOrders` body from [installation.md](installation.md).

### File Attachments

The demo lets the visitor attach a file or an image in the composer. The stock widget does not enable that composer. There is no upload route in this package.

### Feedback Events

The demo sends upvote, downvote, and copy to a backend.

The widget already emits `message:feedback` and `message:copy` on the handle. Post them to a route you own. The chat package does not store feedback.

```js
window.addEventListener('persona:chat-ready', function (event) {
  var handle = event.detail;
  handle.on('message:feedback', function (feedback) {
    fetch('/chat/feedback', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(feedback)
    });
  });
  handle.on('message:copy', function (message) {
    fetch('/chat/copied', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ id: message.id })
    });
  });
});
```

`/chat/feedback` and `/chat/copied` are yours. Put them behind the same auth as the rest of the app. The chat dispatch routes are not these URLs.

### Programmatic Input Focus

The demo uses three Persona 4.39.0 APIs:

- `autoFocusInput: true` focuses the composer after the panel opens. The stock config leaves this at Persona's default, which is false.
- `window.determlyChat.focusInput()` focuses the open composer. It fails when the panel is closed.
- `window.dispatchEvent(new CustomEvent('persona:focusInput'))` focuses every instance on the page.

Call `focusInput` from `persona:chat-ready`, or after you open the panel. The handle is `event.detail` and `window.determlyChat`.

## Reference

### Form Field Reference

The demo is the field list for `DynamicForm`: `text`, `email`, `tel`, `url`, `number`, `date`, `time`, and `textarea`. `width` is `full` or `half`. `required` draws the asterisk. `helper_text` is the line under the control.

That is what `DynamicForm` implements. The demo also shows input masks. This component does not. A masked field is a component you register yourself.

Half fields sit two to a row. A submit posts a sentence to the chat through `sendMessage`, one `name: value` pair per field.

### Event Inspector

The demo shows Persona's event-stream panel and the controller events.

The stock widget does not set `features.showEventStreamToggle`. Subscribe instead. The names and payloads are in [events.md](events.md). `assistant:complete` and `run:finished` are the ones a Laravel page uses to refresh. `approval:requested` and `approval:resolved` match the approval tool. `stream:paused` is a dropped stream, which is separate from `step_await`.

### Shadow-aware Page Context

The demo reads product cards that live inside a shadow root, which a normal DOM walk misses.

The injected widget does not turn that reader on, and it mounts with shadow DOM off. If the data is in Laravel, query it in a server tool. If the data exists only in the open page, read it inside the page tool's `execute`. That function runs in the page, so it can walk a shadow root you know about.

### Scroll Engineering

The demo tunes `features.scrollBehavior` so a streaming thread stays pinned to the latest line without fighting the visitor.

The stock widget does not set `features.scrollBehavior`. Persona 4.39.0's own defaults apply. There is no env key for those options.

## What you turn on yourself

`config/determly-chat.php` already covers artifacts, questions, WebMCP, and the floating, docked, and fullscreen mounts. The gallery items that need a speech engine, file uploads, a custom loader, or a reveal animation are not flags on that file. They stay off so a new install matches the tested widget.

A page script can still use the handle, register page tools, and add components. That is the Laravel path for Talk to Your Data, action handlers, feedback, and focus.
