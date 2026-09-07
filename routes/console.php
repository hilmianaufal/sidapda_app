<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('students:promote')
    ->yearlyOn(7, 1, '00:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
