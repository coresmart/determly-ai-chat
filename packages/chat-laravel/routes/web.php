<?php

declare(strict_types=1);

use Determly\Chat\Laravel\DispatchController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post((string) config('determly-chat.path', 'determly/chat/dispatch'), DispatchController::class)
    ->middleware('web')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('determly.chat.dispatch');
