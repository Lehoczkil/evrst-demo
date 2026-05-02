<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Trim the activity log to a 90-day retention window each night at
// 03:15. Idempotent — empty windows are a no-op.
Schedule::command('activity-log:prune --days=90')
    ->dailyAt('03:15')
    ->onOneServer()
    ->withoutOverlapping();

// Drop image-transform cache variants older than 30 days. The cache
// rebuilds lazily on the next request, so this only reclaims disk for
// no-longer-served sizes/formats.
Schedule::command('image-cache:prune --days=30')
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping();
