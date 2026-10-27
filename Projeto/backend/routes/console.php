<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker de agendamentos (RF06/RF25/RN45): roda a cada minuto.
Schedule::command('agendamentos:processar')->everyMinute()->withoutOverlapping();
