<?php

use App\Http\Controllers\Api\CmsContentController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\SpecialistController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\ValuationRequestController;
use App\Http\Controllers\CmsMediaController;
use App\Http\Middleware\EnsureInquiriesEnabled;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-read')->group(function () {
    Route::get('home', [CmsContentController::class, 'home']);
    Route::get('site-settings', [CmsContentController::class, 'settings']);
    foreach (['results', 'posts', 'testimonials', 'faqs'] as $type) {
        Route::get($type, [CmsContentController::class, 'index'])->defaults('type', $type);
    }
    Route::get('posts/{slug}', [CmsContentController::class, 'post']);
    Route::get('treatments', [TreatmentController::class, 'index']);
    Route::get('treatments/{slug}', [TreatmentController::class, 'show']);
    Route::get('specialists', [SpecialistController::class, 'index']);
    Route::post('valuation-requests', [ValuationRequestController::class, 'store'])->middleware([EnsureInquiriesEnabled::class, 'throttle:public-submit']);
    Route::post('contact-messages', [ContactMessageController::class, 'store'])->middleware([EnsureInquiriesEnabled::class, 'throttle:public-submit']);
});
// Images retain consent checks on every request, independently of Next.js caches.
Route::get('v1/media/{asset}', [CmsMediaController::class, 'publicImage'])->whereUuid('asset');
