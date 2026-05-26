<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');
Route::view('home', 'pages::home')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::redirect('/dashboard', '/{current_team}/passwords')->name('dashboard');

        Route::livewire('/passwords', 'pages::passwords.index')->name('passwords.index');
        Route::livewire('/passwords/create', 'pages::passwords.create')->name('passwords.create');
        Route::livewire('/passwords/{password}/edit', 'pages::passwords.edit')->name('passwords.edit');

        Route::livewire('/credit-cards', 'pages::credit-cards.index')->name('credit-cards.index');
        Route::livewire('/credit-cards/create', 'pages::credit-cards.create')->name('credit-cards.create');
        Route::livewire('/credit-cards/{creditCard}/edit', 'pages::credit-cards.edit')->name('credit-cards.edit');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';

