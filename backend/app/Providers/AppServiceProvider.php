<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\HomePage;
use App\Models\Post;
use App\Models\Result;
use App\Models\SiteSetting;
use App\Models\Specialist;
use App\Models\Testimonial;
use App\Models\Treatment;
use App\Models\ValuationRequest;
use App\Policies\ClinicPolicy;
use App\Providers\Filament\AdminPanelProvider;
use Filament\PanelProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (class_exists(PanelProvider::class)) {
            $this->app->register(AdminPanelProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-read', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('public-submit', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);
        foreach ([Treatment::class, Specialist::class, ValuationRequest::class, ContactMessage::class, Appointment::class] as $model) {
            Gate::policy($model, ClinicPolicy::class);
        }
        foreach ([HomePage::class, SiteSetting::class, Result::class, Post::class, Testimonial::class, Faq::class] as $model) {
            Gate::policy($model, ClinicPolicy::class);
        }
    }
}
