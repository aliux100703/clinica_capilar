<?php

use App\Http\Controllers\CmsMediaController;
use App\Http\Middleware\RequireAdminRole;
use Illuminate\Support\Facades\Route;

Route::middleware(RequireAdminRole::class)->group(function () {
    Route::get('/admin/media/{asset}', [CmsMediaController::class, 'image'])->whereUuid('asset');
    Route::get('/admin/consents/{type}/{record}', [CmsMediaController::class, 'consent'])->whereNumber('record');
});

Route::get('/', function () {
    return view('welcome');
});
