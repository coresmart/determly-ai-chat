<?php

declare(strict_types=1);

use Determly\Chat\Gallery\Catalog;
use Determly\Chat\Gallery\Http\GalleryController;
use Determly\Chat\Laravel\ApproveController;
use Determly\Chat\Laravel\DispatchController;
use Determly\Chat\Laravel\ResumeController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

$examples = Catalog::slugs();

Route::get('/', [GalleryController::class, 'home'])->name('gallery.home');
Route::get('/examples/data/table', [GalleryController::class, 'orders'])->name('gallery.orders');
Route::get('/examples/approval/refunds', [GalleryController::class, 'refunds'])->name('gallery.refunds');
Route::get('/examples/feedback/log', [GalleryController::class, 'activity'])->name('gallery.activity');

Route::post('/examples/{example}/feedback', [GalleryController::class, 'feedback'])->whereIn('example', $examples);
Route::post('/examples/{example}/copied', [GalleryController::class, 'copied'])->whereIn('example', $examples);

Route::get('/examples/{example}', [GalleryController::class, 'show'])
    ->whereIn('example', $examples)
    ->name('gallery.show');

Route::post('/examples/{example}/dispatch', DispatchController::class)
    ->whereIn('example', $examples)
    ->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class]);
Route::post('/examples/{example}/dispatch/resume', ResumeController::class)
    ->whereIn('example', $examples)
    ->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class]);
Route::post('/examples/{example}/dispatch/v1/agents/{agent}/approve', ApproveController::class)
    ->whereIn('example', $examples)
    ->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class]);
