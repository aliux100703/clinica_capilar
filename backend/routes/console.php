<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::command('clinic:revalidate-content')->everyMinute()->withoutOverlapping()->when(fn () => Cache::get('cms.revalidation_pending', false));

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
