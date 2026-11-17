<?php

declare(strict_types=1);

return [
    'inject' => filter_var(env('DETERMINLY_CHAT_INJECT', true), FILTER_VALIDATE_BOOLEAN),
    'path' => 'determly/chat/dispatch',
    'stack' => env('DETERMINLY_CHAT_STACK'),
    'agent_id' => 'determly',
    'agent_name' => env('DETERMINLY_CHAT_NAME', 'Assistant'),
    'title' => env('DETERMINLY_CHAT_TITLE', 'Assistant'),
    'subtitle' => env('DETERMINLY_CHAT_SUBTITLE', 'Ask a question'),
    'window_key' => env('DETERMINLY_CHAT_WINDOW_KEY', 'determlyChat'),
    'webmcp' => filter_var(env('DETERMINLY_CHAT_WEBMCP', true), FILTER_VALIDATE_BOOLEAN),
    'target' => env('DETERMINLY_CHAT_TARGET', 'body'),
    'mount' => env('DETERMINLY_CHAT_MOUNT', 'floating'),
    'dock_side' => env('DETERMINLY_CHAT_DOCK_SIDE', 'right'),
    'dock_width' => env('DETERMINLY_CHAT_DOCK_WIDTH', '420px'),
    'artifacts' => filter_var(env('DETERMINLY_CHAT_ARTIFACTS', true), FILTER_VALIDATE_BOOLEAN),
    'ask_user' => filter_var(env('DETERMINLY_CHAT_ASK', true), FILTER_VALIDATE_BOOLEAN),
    'ai' => env('DETERMINLY_CHAT_AI', 'auto'),
    'provider' => env('DETERMINLY_CHAT_PROVIDER'),
    'model' => env('DETERMINLY_CHAT_MODEL'),
    'instructions' => env('DETERMINLY_CHAT_INSTRUCTIONS', \Determly\Chat\Laravel\Ai\Assistant::INSTRUCTIONS),
];
