<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'home')->name('home');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Volt::route('/dashboard', 'dashboard')->name('dashboard');
    Volt::route('/profile', 'pages.profile')->name('profile');
    Volt::route('/parties/{listeningParty}', 'pages.parties.show')->name('parties.show');
});

require __DIR__.'/auth.php';
