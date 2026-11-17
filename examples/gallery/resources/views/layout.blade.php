<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Determly gallery')</title>
    <style>
        html, body { margin: 0; background: #f8fafc; color: #0f172a; font: 16px/1.5 "Segoe UI", sans-serif; }
        main { max-width: 760px; padding: 28px 24px 80px; }
        h1 { font-size: 1.6rem; margin: 0 0 8px; }
        a { color: #1d4ed8; }
        .summary { margin-top: 0; }
        .hint { color: #475569; }
        .card, table { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .card { padding: 16px; margin: 16px 0; }
        table { border-collapse: collapse; width: 100%; overflow: hidden; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        th { background: #f1f5f9; }
        button { font: inherit; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; }
        button + button { margin-left: 8px; }
        ol, ul { margin: 0; padding-left: 1.2rem; }
        #event-log, #focus-result { min-height: 1.5rem; }
        #shadow-host { min-height: 120px; }
        code { font-family: ui-monospace, monospace; font-size: 0.92em; }
    </style>
    @isset($widget)
        <script type="module">
            import { initializeWebMCPPolyfill } from 'https://cdn.jsdelivr.net/npm/@runtypelabs/persona@4.39.0/dist/webmcp-polyfill.js';
            initializeWebMCPPolyfill();
            document.dispatchEvent(new Event('gallery:webmcp'));
        </script>
        <script>
            window.galleryRegister = function (register) {
                function attempt() {
                    var modelContext = document.modelContext;
                    if (!modelContext || typeof modelContext.registerTool !== 'function') return false;
                    if (window.__galleryTools) return true;
                    window.__galleryTools = true;
                    register(modelContext);
                    return true;
                }
                if (attempt()) return;
                document.addEventListener('gallery:webmcp', attempt);
                var tries = 0;
                var timer = setInterval(function () {
                    if (attempt() || ++tries > 100) clearInterval(timer);
                }, 50);
            };
            window.galleryToken = function () {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.content : '';
            };
        </script>
    @endisset
</head>
<body>
    <main>
        @isset($example)
            <p><a href="/">Gallery</a></p>
            <h1>{{ $example['title'] }}</h1>
            <p class="summary">{{ $example['summary'] }}</p>
            <p class="hint">The assistant is open. Choose a starter chip. State is not kept between loads, so the chips stay available.</p>
        @endisset
        @yield('content')
    </main>
    @stack('scripts')
    @isset($widget)
        {!! $widget !!}
    @endisset
</body>
</html>
