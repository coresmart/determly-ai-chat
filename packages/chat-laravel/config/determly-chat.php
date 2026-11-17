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
    'ai' => env('DETERMINLY_CHAT_AI', 'auto'),
    'provider' => env('DETERMINLY_CHAT_PROVIDER'),
    'model' => env('DETERMINLY_CHAT_MODEL'),
    'instructions' => env('DETERMINLY_CHAT_INSTRUCTIONS', \Determly\Chat\Laravel\Ai\Assistant::INSTRUCTIONS),
];
