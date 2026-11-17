# Integration

Install steps, Laravel AI, Prism, and each front end: [installation.md](installation.md).

One widget, one dispatch route, every Laravel stack. The packages do not ship a Livewire component, a Filament plugin, an Inertia page, or a Blade layout. The app keeps its own UI and listens to the widget.

Widget: `@runtypelabs/persona` 4.39.0. Server events and browser events: [events.md](events.md). A worked chat for Prism and Laravel AI: [examples/support-chat](../examples/support-chat).

## What gets installed

`determly/chat-laravel` registers:

- `POST /determly/chat/dispatch`
- `POST /determly/chat/dispatch/resume`
- `POST /determly/chat/dispatch/v1/agents/determly/approve`
- middleware on the `web` group that inserts the widget before the first `</body>`
- the `<x-determly-chat />` Blade component
- `php artisan determly:chat`

The routes use the `web` middleware group and skip `ValidateCsrfToken`. The widget still sends `X-CSRF-TOKEN` from the `csrf-token` meta tag, plus `X-Requested-With` and `Accept: text/event-stream`.

Set `DETERMLY_CHAT_INJECT=false` and render `<x-determly-chat />` yourself when the layout should own the script tag. Injection and the component emit the same HTML.

`php artisan determly:chat` prints the widget version, the detected stack, the dispatch URL, the `window` key, whether WebMCP is on, the model library, and registered tools.

## Stacks

Detection is by package name, not by version. `DETERMLY_CHAT_STACK` overrides it. The value must be `filament`, `livewire`, `inertia-react`, `inertia-vue`, `inertia`, or `blade`.

| Installed | Reported stack |
|---|---|
| `filament/filament` | `filament`, even when Livewire is also installed |
| `livewire/livewire` | `livewire` |
| `inertiajs/inertia-laravel` and `@inertiajs/react` | `inertia-react` |
| `inertiajs/inertia-laravel` and `@inertiajs/vue3` | `inertia-vue` |
| both Inertia adapters | `inertia` |
| none of those | `blade` |

Filament 4 sits on Livewire 3. Filament 5 sits on Livewire 4. Both are the `filament` stack. The label does not change the markup.

The injector runs on full HTML responses. It skips a response that has the `X-Livewire` header, a non-empty content type other than `text/html`, an empty body, or no `</body>`. The marker `determly-chat-widget` keeps a second copy out of the same response.

Livewire 3 and Livewire 4 component updates send `X-Livewire` and JSON, so the script is not inserted into those responses. `wire:navigate` fetches a full HTML document and sends `X-Livewire-Navigate`, not `X-Livewire`, so the injector adds the script on the new document. Filament SPA mode uses that navigation. The widget script does not set `data-navigate-once`, so a navigation can run the script again. The marker only applies to one response.

An Inertia visit after the first page is JSON and is skipped. The first page is the HTML document, and the script sits before `</body>`, outside the Inertia app node.

## The handle

The injected config sets `windowKey` to `determlyChat`. Listen on `persona:chat-ready` or call `window.determlyChat`. Change the key with `DETERMLY_CHAT_WINDOW_KEY`. An empty value omits `windowKey`, and the handle is only on the ready event.

```js
window.addEventListener('persona:chat-ready', function (event) {
  event.detail.on('assistant:complete', refreshOrders);
});
```

A launcher that waits for the first open fires `persona:chat-ready` at that open, not at page load.

## Refresh the page

The widget is mounted on `document.body`. It cannot call `$wire`, a Filament action, or the Inertia router. The page does, from a handle event or from a page tool.

`assistant:complete` means the assistant message finished. `run:finished` fires once per run. A tool pause does not finish the run. Refresh after `assistant:complete` when a server tool already changed data the page is showing.

| Stack | Call |
|---|---|
| Livewire 3 and 4 | `Livewire.dispatch('orders-refresh')`. The component listens with `#[On('orders-refresh')]`. From inside the component, `$wire.$refresh` re-renders it. |
| Filament 4 and 5 | The same Livewire event. Filament does not have a second channel. |
| Inertia React and Inertia Vue | `router.reload({ only: ['orders'] })`. |
| Blade | Replace the DOM node, or reload the document. |

Streaming, server tools, approval, and run ids stay on the server. The page only refreshes.

## Page tools

A page tool runs in the browser. Register it on `document.modelContext` before the visitor sends a message. `DETERMLY_CHAT_WEBMCP` defaults to true, which sets `config.webmcp.enabled` so Persona snapshots `document.modelContext` on each send. Set `DETERMLY_CHAT_WEBMCP=false` to leave that off.

```js
document.modelContext.registerTool({
  name: 'refresh_orders',
  description: 'Reload the orders table on this page.',
  inputSchema: {
    type: 'object',
    properties: {
      order_id: { type: 'string', description: 'Limit the refresh to one order.' }
    }
  },
  annotations: { readOnlyHint: true },
  execute: async function (input) {
    refreshOrders(input.order_id);
    return { content: [{ type: 'text', text: 'Orders refreshed.' }] };
  }
});
```

Persona sends the tool on `clientTools` with the bare name, the description, the JSON schema, and `origin: webmcp`. The model sees that name and that schema. The server does not run it. The pause is `await` with `awaitReason: local_tool_required`, `toolName`, `toolCallId`, `parameters`, and `origin: webmcp`. Persona 4.39.0 then calls `execute` and posts the result to `/resume`.

A name that starts with `webmcp:` is also treated as a page tool. A string in `clientTools` is a page tool with a generic `input` string argument. A page tool whose name is already a registered server tool keeps the server schema, and the runner still pauses so the browser runs it.

Page tools confirm before they run. The injected script sets `webmcp.autoApprove` to allow a tool whose `annotations.readOnlyHint` is true. Anything else asks the visitor. That matches the Persona 4.39.0 confirm gate.

The tool result is untrusted data. The next model step is told to treat it as data, not as instructions.

Do not block a tool on a web task. A tool that starts one returns a run id. Search and fetch do not spend credits.

## Laravel AI and Prism

The responder is selected by what is installed and by `DETERMLY_CHAT_AI`.

| `DETERMLY_CHAT_AI` | Result |
|---|---|
| `auto` | Echo if neither library is installed. Laravel AI if only `laravel/ai` is installed. Prism if only `prism-php/prism` is installed. An error if both are installed. |
| `echo` | The echo responder. |
| `laravel-ai` | Laravel AI. Provider and model are optional. |
| `prism` | Prism. `DETERMLY_CHAT_PROVIDER` and `DETERMLY_CHAT_MODEL` are required. |

The adapters were written against `laravel/ai` 1.4.0 and `prism-php/prism` 0.103.0. The published package does not require either library.

Register each server tool once on `Determly\Chat\ToolRegistry`. Both libraries read that registry. The runner executes the tool. Each library is limited to one model step so it does not execute the tool as well. After the tool, the runner calls the model again.

The support chat example is the concrete pair: one `lookup_order` tool, a Prism env file, a Laravel AI env file, and `resources/js/determly-chat.js` for the refresh. See [examples/support-chat](../examples/support-chat).

Components, the artifact pane, questions, follow-up chips, and the floating, docked, and fullscreen mounts are in [components.md](components.md).
