<?php

declare(strict_types=1);

return [
    'inject' => filter_var(env('DETERMLY_CHAT_INJECT', true), FILTER_VALIDATE_BOOLEAN),
    'path' => 'determly/chat/dispatch',
    'stack' => env('DETERMLY_CHAT_STACK'),
    'agent_id' => 'determly',
    'agent_name' => env('DETERMLY_CHAT_NAME', 'Assistant'),
    'title' => env('DETERMLY_CHAT_TITLE', 'Assistant'),
    'subtitle' => env('DETERMLY_CHAT_SUBTITLE', 'Ask a question'),
    'window_key' => env('DETERMLY_CHAT_WINDOW_KEY', 'determlyChat'),
    'webmcp' => filter_var(env('DETERMLY_CHAT_WEBMCP', true), FILTER_VALIDATE_BOOLEAN),
    'target' => env('DETERMLY_CHAT_TARGET', 'body'),
    'mount' => env('DETERMLY_CHAT_MOUNT', 'floating'),
    'dock_side' => env('DETERMLY_CHAT_DOCK_SIDE', 'right'),
    'dock_width' => env('DETERMLY_CHAT_DOCK_WIDTH', '420px'),
    'artifacts' => filter_var(env('DETERMLY_CHAT_ARTIFACTS', true), FILTER_VALIDATE_BOOLEAN),
    'ask_user' => filter_var(env('DETERMLY_CHAT_ASK', true), FILTER_VALIDATE_BOOLEAN),
    'appearance' => [
        'color_scheme' => env('DETERMLY_CHAT_COLOR_SCHEME', 'light'),
        'brand' => [
            'accent' => env('DETERMLY_CHAT_ACCENT'),
            'neutral' => env('DETERMLY_CHAT_NEUTRAL'),
            'font' => env('DETERMLY_CHAT_FONT'),
            'radius' => env('DETERMLY_CHAT_RADIUS'),
            'density' => env('DETERMLY_CHAT_DENSITY'),
            'elevation' => env('DETERMLY_CHAT_ELEVATION'),
        ],
        'theme' => [],
        'dark_theme' => [],
    ],
    'ai' => env('DETERMLY_CHAT_AI', 'auto'),
    // Provider and model names only. The API key is not a parameter here.
    // OpenRouter reads OPENROUTER_API_KEY in Laravel AI config/ai.php and Prism config/prism.php.
    'provider' => env('DETERMLY_CHAT_PROVIDER', 'openrouter'),
    'model' => env('DETERMLY_CHAT_MODEL', 'openai/gpt-4.1-mini'),
    'instructions' => env('DETERMLY_CHAT_INSTRUCTIONS', \Determly\Chat\Laravel\Ai\Assistant::INSTRUCTIONS),
];
