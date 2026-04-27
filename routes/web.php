<?php

use App\Livewire\Dashboard;
use App\Livewire\GestionarUsuarios\UsuariosComponent;
use App\Livewire\Servicios\TodosServicios;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', UsuariosComponent::class)->name('usuarios.gestion');
    Route::get('/servicios', TodosServicios::class)->name('servicios.todos');
});

require __DIR__.'/settings.php';
