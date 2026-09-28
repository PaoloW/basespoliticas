<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\CentroController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VotoController;

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
// Personas sin cuenta de usuario, para el modal paginado en el servidor.
Route::get('usuarios/personas', [UsuarioController::class, 'personas'])
    ->name('usuarios.personas')
    ->middleware('auth');

Route::resource('usuarios', UsuarioController::class)->except(['show'])->middleware('auth');
Route::resource('personas', PersonaController::class)->except(['show'])->middleware('auth');
Route::resource('centros', CentroController::class)->except(['show'])->middleware('auth');
Route::resource('mesas', MesaController::class)->except(['show'])->middleware('auth');
Route::resource('cargos', CargoController::class)->except(['show'])->middleware('auth');
Route::resource('bases', BaseController::class)->except(['show'])->parameters(['bases' => 'base'])->middleware('auth');
Route::resource('partidos', PartidoController::class)->except(['show'])->middleware('auth');
Route::resource('votos', VotoController::class)->except(['show'])->middleware('auth');

// Afiliados: búsquedas AJAX y listados paginados (modales) antes del resource.
Route::get('afiliados/buscar-persona', [AfiliadoController::class, 'buscarPersona'])
    ->name('afiliados.buscarPersona')
    ->middleware('auth');
Route::get('afiliados/personas', [AfiliadoController::class, 'personas'])
    ->name('afiliados.personas')
    ->middleware('auth');
Route::resource('afiliados', AfiliadoController::class)->except(['show'])->middleware('auth');
