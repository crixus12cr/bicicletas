<?php

use App\Livewire\GestionarUsuarios\UsuariosComponent;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', UsuariosComponent::class)->name('usuarios.gestion');
});

require __DIR__.'/settings.php';
