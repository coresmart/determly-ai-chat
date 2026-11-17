# Events

Two event surfaces. The server streams Persona SSE frames. The widget then emits handle events in the browser. Both are the `@runtypelabs/persona` 4.39.0 vocabulary. Determly does not add a second protocol.

The older Runtype wire (`step_chunk`, `flow_complete`) is not this protocol.

## SSE envelope

`Content-Type: text/event-stream`. Each frame is:

```text
event: text_delta
data: {"type":"text_delta","executionId":"exec_1","seq":3,"id":"text_1","delta":"Hello"}

```

Every `data` object has `type`, `executionId`, and `seq`. `seq` starts at 0 for that HTTP response and increases by one. `type` matches the `event` name. JSON is encoded with unescaped slashes and unescaped unicode.

A dispatch, a resume, and an approval decision are each their own HTTP response. Each one starts with `execution_start`. Resume and approval reuse the `executionId` from the paused turn.

## Server frames

| Event | When | Fields besides the envelope |
|---|---|---|
| `execution_start` | The response opens | `kind` (`agent`), `agentId`, `agentName`, `startedAt` |
| `turn_start` | The assistant turn opens | `id`, `role` (`assistant`), `iteration` (`1`) |
| `reasoning_start` | A reasoning block opens | `id`, `scope` (`turn`) |
| `reasoning_delta` | Reasoning text | `id`, `delta` |
| `reasoning_complete` | The reasoning block closes | `id` |
| `text_start` | An answer block opens | `id` |
| `text_delta` | Answer text | `id`, `delta` |
| `text_complete` | The answer block closes | `id` |
| `tool_start` | A tool call is shown | `toolCallId`, `toolName`, `toolType` (`local`), `parameters`, `iteration` (`1`) |
| `tool_complete` | A server tool finished | `toolCallId`, `success`, `result` |
| `agent_approval_start` | A server tool needs a decision | `approvalId`, `toolName`, `toolType`, `description`, `parameters`, `executionId` |
| `agent_approval_complete` | The decision arrived | `approvalId`, `decision` (`approved` or `denied`), `executionId` |
| `step_await` | The turn is paused | `awaitReason`, `executionId`, plus the fields below |
| `turn_complete` | The assistant turn ends | `id`, `role`, `iteration`, `stopReason` (`end_turn`), `completedAt` |
| `artifact_start` | An artifact pane opens | `id`, `artifactType` (`markdown` or `component`), `title`, and `component` when the type is `component` |
| `artifact_delta` | Markdown streams into the pane | `id`, `delta` |
| `artifact_update` | Component props for the pane | `id`, `props`, `component` |
| `artifact_complete` | The artifact finished | `id` |
| `execution_complete` | The response succeeded | `kind` (`agent`), `success` (`true`), `completedAt` |
| `execution_error` | The response failed | `kind` (`agent`), `error.code` (`adapter_error`), `error.message` |

A text turn is `execution_start`, `turn_start`, `text_start`, one or more `text_delta`, `text_complete`, `turn_complete`, `execution_complete`.

Reasoning closes before the answer or before a tool. A tool closes an open text block first, then sends `tool_start` and `tool_complete`. Quick replies are a `suggest_replies` tool call. The arguments are `parameters.suggestions`. The result text is `Suggestions shown to the user.`

Deltas are the text. The server does not send the full answer again at the end.

`execution_error` ends the response. There is no `execution_complete` after it. A pause also ends the response without `execution_complete`. `Session` will not append `execution_complete` once the emitter has paused or failed.

## Pause and resume

Approval pause, `awaitReason: approval_required`:

```json
{
  "awaitReason": "approval_required",
  "approvalId": "approval_call_1",
  "toolName": "lookup_order",
  "toolCallId": "call_1",
  "toolType": "local",
  "description": "Look up one order by its id and return the status.",
  "parameters": { "order_id": "1001" }
}
```

The widget posts `{dispatch}/v1/agents/{agentId}/approve`. With the default dispatch path that URL is `/determly/chat/dispatch/v1/agents/determly/approve`. The body is `executionId`, `approvalId`, `decision` (`approved` or `denied`), and optional `remember`. `remember` stores the tool name so the next call does not ask again. Denied does not run the tool. The next SSE response starts with `execution_start`, then `agent_approval_complete`.

Page tool pause, `awaitReason: local_tool_required`:

```json
{
  "awaitReason": "local_tool_required",
  "toolName": "refresh_orders",
  "toolCallId": "call_1",
  "parameters": {},
  "origin": "webmcp"
}
```

`origin` is present when the dispatch body sent that tool with `origin: webmcp`. Persona 4.39.0 uses that field to run the page tool. A client tool that was only a name, or a name that already starts with `webmcp:`, pauses without `origin`. The widget still resumes those calls when it recognizes the name.

The widget posts `{dispatch}/resume` with:

```json
{
  "executionId": "exec_1",
  "toolOutputs": {
    "call_1": {
      "content": [{ "type": "text", "text": "Orders refreshed." }]
    }
  }
}
```

`toolOutputs` is keyed by `toolCallId`. The transcript is not sent again. The server reads the stored execution, appends the output as untrusted data, and calls the model again.

## Widget handle events

Subscribe after the widget is ready. The injected script sets `windowKey` to `determlyChat` unless `DETERMINLY_CHAT_WINDOW_KEY` changes it.

```js
window.addEventListener('persona:chat-ready', function (event) {
  event.detail.on('assistant:complete', function (message) {
    // message.content is the visible assistant text
  });
});
```

`event.detail` is the handle. `window.determlyChat` is the same handle when `windowKey` is set. A deferred launcher fires `persona:chat-ready` after the first open.

These names are `AgentWidgetControllerEventMap` in `@runtypelabs/persona` 4.39.0.

| Event | Payload |
|---|---|
| `messages:changed` | The transcript. Streaming text is coalesced per animation frame. |
| `run:started` | `{ runId, timestamp }`. Includes retries and restored runs. |
| `run:finished` | `{ runId, timestamp, outcome }`. `outcome` is `success`, `error`, or `cancel`. One event per run. A tool or approval pause keeps the same `runId`. |
| `user:message` | The user message. |
| `assistant:message` | An assistant message while it is updating. |
| `assistant:complete` | The assistant message when that message is done. |
| `voice:state` | Voice state. |
| `voice:status` | Voice status. |
| `dictation:state` | Dictation lifecycle. Audio samples stay in the widget. |
| `action:detected` | The widget parsed an action from the answer. |
| `action:resubmit` | The visitor asked to run that action again. |
| `widget:opened` | `{ open, source, timestamp }`. `source` is `user`, `auto`, `api`, or `system`. |
| `widget:closed` | The same shape as `widget:opened`. |
| `widget:state` | `{ open, launcherEnabled, voiceActive, streaming }`. |
| `message:feedback` | The visitor rated a message. |
| `message:copy` | The visitor copied a message. |
| `message:read-aloud` | The visitor started read-aloud. |
| `eventStream:opened` | `{ timestamp }`. |
| `eventStream:closed` | `{ timestamp }`. |
| `approval:requested` | `{ approval, message }`. |
| `approval:resolved` | `{ approval, decision }`. |
| `stream:paused` | `{ executionId, after }`. A durable stream dropped and the widget is about to reconnect. |
| `stream:resuming` | `{ executionId, after, attempt }`. `attempt` starts at 1. |
| `stream:resumed` | `{ executionId, after }`. The turn reached its end after a reconnect. |
| `conversation:executionState` | The conversation execution state. |
| `history:opened` | `{ presentation, returnSurface, timestamp }`. |
| `history:closed` | `{ returnSurface, timestamp }`. |
| `history:conversationOpened` | `{ conversationId, title, scope, timestamp }`. |
| `history:conversationStarted` | `{ conversationId, timestamp }`. `conversationId` may be null. |
| `history:conversationDeleted` | `{ conversationId, scope, wasActive, timestamp }`. `wasActive` means the open transcript was replaced. |
| `history:cleared` | `{ deleted, scope, targetId, timestamp }`. `targetId` may be null. |
| `history:identityReset` | `{ remoteRevocationConfirmed, timestamp }`. |
| `history:identityStatusChanged` | `{ status, timestamp }`. Emitted when the status or the reason changes. |

Use `assistant:complete` or `run:finished` to refresh the page after a server tool. Use a WebMCP page tool when the browser has to do the work before the model continues. The handle event does not receive the raw SSE frame. Read `docs/integration.md` for the refresh calls on each stack.
