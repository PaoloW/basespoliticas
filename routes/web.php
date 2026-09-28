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
use App\Http\Controllers\PersoneroController;
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
// El middleware `acceso` restringe cada ruta al menú (acceso) correspondiente.
// Búsqueda de una persona por su DNI (para asignarla como nuevo usuario).
Route::get('usuarios/buscar-persona', [UsuarioController::class, 'buscarPersonaPorDni'])
    ->name('usuarios.buscarPersona')
    ->middleware('auth')
    ->middleware('acceso');
// Personas sin cuenta de usuario, para el modal paginado en el servidor.
Route::get('usuarios/personas', [UsuarioController::class, 'personas'])
    ->name('usuarios.personas')
    ->middleware('auth')
    ->middleware('acceso');

// Guardar el tema de la interfaz (claro/oscuro) del usuario autenticado.
Route::post('usuarios/tema', [UsuarioController::class, 'guardarTema'])
    ->name('usuarios.tema')
    ->middleware('auth');

Route::resource('usuarios', UsuarioController::class)->except(['show'])->middleware('auth')->middleware('acceso');
Route::resource('personas', PersonaController::class)->except(['show'])->middleware('auth')->middleware('acceso');
Route::resource('centros', CentroController::class)->except(['show'])->middleware('auth')->middleware('acceso');
Route::resource('mesas', MesaController::class)->except(['show'])->middleware('auth')->middleware('acceso');
Route::resource('cargos', CargoController::class)->except(['show'])->middleware('auth')->middleware('acceso');
Route::resource('bases', BaseController::class)->except(['show'])->parameters(['bases' => 'base'])->middleware('auth')->middleware('acceso');
Route::resource('partidos', PartidoController::class)->except(['show'])->middleware('auth')->middleware('acceso');

// Personeros: búsquedas AJAX y listado paginado (modal) antes del resource.
Route::get('personeros/buscar-persona', [PersoneroController::class, 'buscarPersona'])
    ->name('personeros.buscarPersona')
    ->middleware('auth')
    ->middleware('acceso');
Route::get('personeros/personas', [PersoneroController::class, 'personas'])
    ->name('personeros.personas')
    ->middleware('auth')
    ->middleware('acceso');
Route::resource('personeros', PersoneroController::class)->except(['show'])->middleware('auth')->middleware('acceso');

// Conteo de votos de una mesa (AJAX) antes del resto de rutas.
Route::get('votos/mesa/{mesa}/conteo', [VotoController::class, 'conteo'])
    ->name('votos.conteo')
    ->middleware('auth')
    ->middleware('acceso');

// Búsqueda de mesa con su personero y conteos, búsqueda de persona por DNI
// (para registrar personero al vuelo) y listado paginado de mesas para el modal.
Route::get('votos/buscar-personero', [VotoController::class, 'buscarPersoneroPorDni'])
    ->name('votos.buscarPersonero')
    ->middleware('auth')
    ->middleware('acceso');
Route::get('votos/buscar-persona', [VotoController::class, 'buscarPersona'])
    ->name('votos.buscarPersona')
    ->middleware('auth')
    ->middleware('acceso');
Route::get('votos/personeros-modal', [VotoController::class, 'personerosModal'])
    ->name('votos.personerosModal')
    ->middleware('auth')
    ->middleware('acceso');

// Ver conteo de votos: reporte de personeros que registraron votos.
Route::get('votos', [VotoController::class, 'index'])
    ->name('votos.index')
    ->middleware('auth')
    ->middleware('acceso');

// Registrar conteo de votos: formulario directo para el usuario activo.
Route::get('votos/registrar', [VotoController::class, 'registrar'])
    ->name('votos.registrar')
    ->middleware('auth')
    ->middleware('acceso');
Route::post('votos/registrar', [VotoController::class, 'guardar'])
    ->name('votos.guardar')
    ->middleware('auth')
    ->middleware('acceso');

// Detalle y corrección de un conteo puntual (administrador).
Route::get('votos/{voto}/edit', [VotoController::class, 'edit'])
    ->name('votos.edit')
    ->middleware('auth')
    ->middleware('acceso');
Route::put('votos/{voto}', [VotoController::class, 'update'])
    ->name('votos.update')
    ->middleware('auth')
    ->middleware('acceso');
Route::delete('votos/{voto}', [VotoController::class, 'destroy'])
    ->name('votos.destroy')
    ->middleware('auth')
    ->middleware('acceso');

// Afiliados: búsquedas AJAX y listados paginados (modales) antes del resource.
Route::get('afiliados/buscar-persona', [AfiliadoController::class, 'buscarPersona'])
    ->name('afiliados.buscarPersona')
    ->middleware('auth')
    ->middleware('acceso');
Route::get('afiliados/personas', [AfiliadoController::class, 'personas'])
    ->name('afiliados.personas')
    ->middleware('auth')
    ->middleware('acceso');
Route::resource('afiliados', AfiliadoController::class)->except(['show'])->middleware('auth')->middleware('acceso');
