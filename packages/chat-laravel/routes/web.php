<?php

declare(strict_types=1);

use Determly\Chat\Laravel\ApproveController;
use Determly\Chat\Laravel\DispatchController;
use Determly\Chat\Laravel\ResumeController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

$path = (string) config('determly-chat.path', 'determly/chat/dispatch');

Route::middleware('web')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () use ($path): void {
        Route::post($path, DispatchController::class)->name('determly.chat.dispatch');
        Route::post($path.'/resume', ResumeController::class)->name('determly.chat.resume');
        Route::post($path.'/v1/agents/{agent}/approve', ApproveController::class)->name('determly.chat.approve');
    });
