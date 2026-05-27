<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Task Scheduling
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands and schedule your background task executions.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the expired status updates to prune automatically every hour
Schedule::command('app:prune-expired-statuses')->hourly();

// Purge expired temporary users (TTL 1 hour) every minute
Schedule::call(function () {
    \App\Models\User::where('role', 'temp')
        ->where('created_at', '<=', now()->subHour())
        ->each(function ($user) {
            // Delete personal access tokens and sessions
            \Illuminate\Support\Facades\DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', \App\Models\User::class)
                ->delete();
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
            // Delete user (cascades database settings and channel memberships)
            $user->delete();
        });
})->everyMinute();
