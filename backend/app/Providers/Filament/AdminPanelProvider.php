<?php

namespace App\Providers\Filament;

use App\Filament\Cms\FaqResource;
use App\Filament\Cms\HomePageResource;
use App\Filament\Cms\PostResource;
use App\Filament\Cms\ResultResource;
use App\Filament\Cms\SiteSettingResource;
use App\Filament\Cms\SpecialistResource;
use App\Filament\Cms\TestimonialResource;
use App\Filament\Cms\TreatmentResource;
use App\Http\Middleware\RequireAdminRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->login()
            ->brandName('Clínica Capilar')
            ->resources([
                HomePageResource::class,
                TreatmentResource::class,
                SpecialistResource::class,
                ResultResource::class,
                PostResource::class,
                TestimonialResource::class,
                FaqResource::class,
                SiteSettingResource::class,
            ])
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class,
                SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, RequireAdminRole::class], isPersistent: true);
    }
}
