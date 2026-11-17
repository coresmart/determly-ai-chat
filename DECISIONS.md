# Decisions

## This repository

https://github.com/coresmart/determly-ai-chat is a Composer monorepo, MIT licensed, matching the `LICENSE` file. The root `composer.json` is a private workspace named `determly/ai-chat-workspace`. It is not an installable package.

Installable libraries live under `packages/`, each with its own `composer.json`:

- `determly/chat` is the Persona wire and has no Laravel dependency.
- `determly/chat-laravel` depends on `determly/chat` and mounts the widget.

An app requires those packages by path. Requiring the GitHub repository itself does not install them, because Composer only reads the root `composer.json`.

## PHP 8.3

The packages require PHP 8.3. Laravel 12 and 13 both run on it.

## One widget for every installed stack

The UI is the Persona widget from https://www.persona-chat.dev (`@runtypelabs/persona` 4.39.0). The wire matches `packages/persona-wire/src/index.ts` in `runtypelabs/persona` at that widget version.

There is no Livewire package, React package, or Vue package. Those would wrap the same vanilla widget and drift. `determly/chat-laravel` detects the installed stack and mounts the same script.

- Filament wins when Livewire is also installed, because Filament apps include Livewire.
- The script is injected into full HTML responses.
- Livewire update requests and Inertia JSON visits are left alone.
- The dispatch route is same-origin and CSRF-exempt so the widget can post. The session cookie still applies.

Until an AI library is bound, dispatch uses `EchoResponder`, which streams the latest user message and two suggestions. An app replaces the `Determly\Chat\Run\Responder` binding. The runner executes registered tools, pauses on `step_await` for approval or a client tool, and continues from `POST {dispatch}/resume` or `{dispatch}/v1/agents/{agent}/approve`. The execution is stored because those requests do not include the transcript. Tools from any recipe register on `ToolRegistry`. A duplicate tool name is refused.

## Laravel AI and Prism

`determly/chat-laravel` streams through `laravel/ai` 1.4.0 or `prism-php/prism` 0.103.0 when that library is installed. Neither library is a requirement of the package. Constraints used while writing the adapters: `laravel/ai` ^1.4 and `prism-php/prism` ^0.103.

`DETERMINLY_CHAT_AI=auto` selects the only installed library. Echo remains the responder when neither is installed. If both are installed, auto does not pick one. Set `DETERMINLY_CHAT_AI` to `laravel-ai` or `prism`. `echo` forces the echo responder.

The runner keeps tool execution, approval, and resume. The library proposes the next step. Laravel AI is limited with `MaxSteps(1)`, which stops it from running the tool on that step. Prism is limited with `withMaxSteps(1)`, and its tool callback returns an empty string, because Prism would otherwise run the callback before the runner. The next responder call sees the runner's tool result, prefixed as untrusted data. Page tools and `requiresApproval()` still pause the Persona stream. A tool that starts a web task returns a run id and must not call `agent->run()`. Search and fetch do not spend credits.
