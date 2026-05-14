<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', EnsureTeamMembership::class])->scopeBindings()->group(function () {
    Route::livewire('teams/{team}/vault', 'pages::vault.index')->name('vault.index');

    Route::livewire('teams/{team}/passwords', 'pages::passwords.index')->name('passwords.index');
    Route::livewire('teams/{team}/passwords/create', 'pages::passwords.create')->name('passwords.create');
    Route::livewire('teams/{team}/passwords/{password}', 'pages::passwords.show')->name('passwords.show');
    Route::livewire('teams/{team}/passwords/{password}/edit', 'pages::passwords.edit')->name('passwords.edit');

    Route::livewire('teams/{team}/credit-cards', 'pages::credit-cards.index')->name('credit-cards.index');
    Route::livewire('teams/{team}/credit-cards/create', 'pages::credit-cards.create')->name('credit-cards.create');
    Route::livewire('teams/{team}/credit-cards/{creditCard}', 'pages::credit-cards.show')->name('credit-cards.show');
    Route::livewire('teams/{team}/credit-cards/{creditCard}/edit', 'pages::credit-cards.edit')->name('credit-cards.edit');
});
