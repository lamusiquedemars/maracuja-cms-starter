<?php

use App\Support\Modules;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (Modules::enabled('conversations')) {
    Schedule::command('conversations:prune')->dailyAt('03:30');
}

if (Modules::enabled('acquisition')) {
    Schedule::command('acquisition:sync-summary')->hourly();
}
