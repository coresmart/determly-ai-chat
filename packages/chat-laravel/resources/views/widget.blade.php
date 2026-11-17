{!! \Determly\Chat\Laravel\Widget::html(
    url((string) config('determly-chat.path')),
    (string) config('determly-chat.title'),
    (string) config('determly-chat.subtitle'),
    (string) config('determly-chat.window_key', 'determlyChat'),
    (bool) config('determly-chat.webmcp', true),
) !!}
