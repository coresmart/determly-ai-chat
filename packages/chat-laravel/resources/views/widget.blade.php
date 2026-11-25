{!! \Determly\Chat\Laravel\Widget::html(
    url((string) config('determly-chat.path')),
    (string) config('determly-chat.title'),
    (string) config('determly-chat.subtitle'),
    (string) config('determly-chat.window_key', 'determlyChat'),
    (bool) config('determly-chat.webmcp', true),
    (string) config('determly-chat.target', 'body'),
    (string) config('determly-chat.mount', 'floating'),
    (string) config('determly-chat.dock_side', 'right'),
    (string) config('determly-chat.dock_width', '420px'),
    (bool) config('determly-chat.artifacts', true),
    (bool) config('determly-chat.ask_user', true),
    \Determly\Chat\Laravel\Theme::resolve(config('determly-chat.appearance')),
) !!}
