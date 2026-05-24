<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings', 'pages::settings')->name('settings');

    Route::livewire('teams', 'pages::teams.switch')->name('teams.switch');
    Route::livewire('teams/{team}', 'pages::teams.settings')
        ->middleware(EnsureTeamMembership::class)
        ->name('teams.settings');
});
