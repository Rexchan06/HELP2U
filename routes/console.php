<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Extra safety net alongside the lazy expiry in controllers: volunteers
// who never log in still get their stale matches expired every hour.
Schedule::command('matches:expire')->hourly();
