<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\CentroController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PersonaController;
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
// Gestión
// ---------------------------------------------------------------------------
// Búsqueda de una persona por su DNI (para asignarla como nuevo usuario).
Route::get('usuarios/buscar-persona', [UsuarioController::class, 'buscarPersonaPorDni'])
    ->name('usuarios.buscarPersona')
    ->middleware('auth');

Route::resource('usuarios', UsuarioController::class)->except(['show'])->middleware('auth');
Route::resource('personas', PersonaController::class)->except(['show'])->middleware('auth');
Route::resource('centros', CentroController::class)->except(['show'])->middleware('auth');
Route::resource('mesas', MesaController::class)->except(['show'])->middleware('auth');
Route::resource('cargos', CargoController::class)->except(['show'])->middleware('auth');
Route::resource('bases', BaseController::class)->except(['show'])->parameters(['bases' => 'base'])->middleware('auth');
