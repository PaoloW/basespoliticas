<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;

// ---------------------------------------------------------------------------
// Autenticación
// ---------------------------------------------------------------------------
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/home', function () {
    return view('home');
})->name('home')->middleware('auth');

// ---------------------------------------------------------------------------
// Gestión de usuarios
// ---------------------------------------------------------------------------
Route::resource('usuarios', UsuarioController::class)->except(['show'])->middleware('auth');
