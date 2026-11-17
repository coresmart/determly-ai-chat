# Components and features

The widget is `@runtypelabs/persona` 4.39.0. These are the advanced features Determly turns on, and the three components it registers. The full gallery, including the demos this package leaves off, is in [advanced-examples.md](advanced-examples.md). Voice, file attachments, and a custom speech provider stay in the app, because each one needs its own service.

## Components in the transcript

`DynamicForm`, `ProductCard`, and `Notice` are registered on `config.components` before the widget starts. A reply can stay plain text. When the reply is one JSON object with a `text` field and a `component` name, the widget shows `text` and renders the component. The stream parser does that. It does not require every answer to be JSON.

```json
{
  "text": "Tell me how to reach you.",
  "component": "DynamicForm",
  "props": {
    "title": "Contact",
    "submit_text": "Send",
    "fields": [
      { "label": "Email", "type": "email", "required": true, "width": "half" },
      { "label": "Phone", "type": "tel", "width": "half" },
      { "label": "Notes", "type": "textarea", "helper_text": "Optional" }
    ]
  }
}
```

Field types are `text`, `email`, `tel`, `url`, `number`, `date`, `time`, and `textarea`. `width` is `full` or `half`. `required` and `helper_text` are optional.

`ProductCard` props are `title`, `price`, `description`, and `button`. The button sends "Add {title} to the cart." `Notice` props are `title`, `body`, and `tone` (`info`, `success`, or `warning`).

A form submit and the product button call `sendMessage` on `window.determlyChat` (or `DETERMLY_CHAT_WINDOW_KEY`). The page can register more components on `window.DetermlyChatComponents` before the widget script runs. The same names work in an artifact pane.

The model instructions in `determly/chat-laravel` describe this JSON. Echo mode ignores it and keeps streaming plain text.

## Artifact pane

`DETERMLY_CHAT_ARTIFACTS` defaults to on. The widget enables `features.artifacts` for `markdown` and `component`.

Two server tools open the pane. The runner emits Persona artifact frames and does not show the tool call in the transcript.

| Tool | Arguments | Frames |
|---|---|---|
| `emit_artifact_markdown` | `title`, `content` | `artifact_start`, `artifact_delta`, `artifact_complete` |
| `emit_artifact_component` | `title`, `component`, `props` (a JSON object string) | `artifact_start`, `artifact_update`, `artifact_complete` |

`artifact_delta` carries `delta`. `artifact_update` carries `props` and `component`. The widget opens the side pane on desktop and a drawer on a narrow screen.

## Questions and follow-ups

`DETERMLY_CHAT_ASK` defaults to on. That sets `features.askUserQuestion.expose`, so the widget offers `ask_user_question` on each turn. The server pauses with `local_tool_required`. The widget shows the answer sheet and posts `/resume`.

`suggestions.followUps.expose` is on, so the model can call `suggest_replies`. The widget shows the chips and resumes. Starter chips are still "What can you do?" and "What tools are available?".

Approval is unchanged: a tool with `requiresApproval()` pauses for `approval_required`.

Reasoning and tool calls stay visible (`features.showReasoning`, `features.showToolCalls`).

## Layout

`DETERMLY_CHAT_MOUNT` is `floating` (the default), `docked`, or `fullscreen`.

| Mount | What the visitor sees |
|---|---|
| `floating` | Launcher in the corner. `target` stays `body`. |
| `docked` | Side panel around `DETERMLY_CHAT_TARGET`. Set that to a region such as `#workspace`. `body` and `html` are rejected. `DETERMLY_CHAT_DOCK_SIDE` is `left` or `right`. `DETERMLY_CHAT_DOCK_WIDTH` defaults to `420px`. The region needs a real height. |
| `fullscreen` | Launcher off, `fullHeight` on. Point `DETERMLY_CHAT_TARGET` at the element that should fill. |

`php artisan determly:chat` prints the mount, the target, artifacts, ask-user, the theme, and the component names. A docked target of `body` or `html` fails that command.

## Theme

The widget color, type, and corners come from Persona 4.39.0's `colorScheme`, `brand`, and token tree. Determly reads them from `config/determly-chat.php` under `appearance`, and from `Determly\Chat\Laravel\Theme` when you assign that object in a service provider. Empty values leave Persona's colors in place.

The env keys are `DETERMLY_CHAT_COLOR_SCHEME`, `DETERMLY_CHAT_ACCENT`, `DETERMLY_CHAT_NEUTRAL`, `DETERMLY_CHAT_FONT`, `DETERMLY_CHAT_RADIUS`, `DETERMLY_CHAT_DENSITY`, and `DETERMLY_CHAT_ELEVATION`. Nested `palette`, `semantic`, and `components` tokens are set in the config file or in PHP. The full lists, the config file, and the `Theme` chain are in [installation.md](installation.md). The gallery page `/examples/theme` is one `Theme` already passed into the widget.
