<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\Personero;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAcceso
{
    /**
     * Menú (acceso) requerido por cada nombre de ruta. Las rutas que no
     * aparecen en la lista no exigen un menú específico: home, login,
     * cerrar sesión o guardar el tema de la interfaz.
     *
     * @var array<string, string|list<string>>
     */
    private const RUTAS = [
        'usuarios.index' => Menu::USUARIOS,
        'usuarios.create' => Menu::USUARIOS,
        'usuarios.store' => Menu::USUARIOS,
        'usuarios.edit' => Menu::USUARIOS,
        'usuarios.update' => Menu::USUARIOS,
        'usuarios.destroy' => Menu::USUARIOS,
        'usuarios.buscarPersona' => Menu::USUARIOS,
        'usuarios.personas' => Menu::USUARIOS,

        'personas.index' => Menu::PERSONAS,
        'personas.edit' => Menu::PERSONAS,
        'personas.update' => Menu::PERSONAS,
        'personas.destroy' => Menu::PERSONAS,

        'centros.*' => Menu::CENTROS,
        'mesas.*' => Menu::MESAS,
        'cargos.*' => Menu::CARGOS,
        'bases.*' => Menu::BASES,
        'partidos.*' => Menu::PARTIDOS,

        'afiliados.*' => Menu::AFILIADOS_BASES,
        'personeros.*' => Menu::PERSONEROS,

        'votos.index' => Menu::VER_CONTEOS,
        'votos.conteo' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
        'votos.buscarPersonero' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
        'votos.personerosModal' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
        'votos.registrar' => Menu::REGISTRAR_CONTEOS,
        'votos.guardar' => Menu::REGISTRAR_CONTEOS,
        'votos.edit' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
        'votos.update' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
        'votos.destroy' => [Menu::VER_CONTEOS, Menu::REGISTRAR_CONTEOS],
    ];

    /**
     * Menú que debe tener quien registra una persona desde otro módulo.
     *
     * @var array<string, string>
     */
    private const ORIGENES = [
        'personas' => Menu::PERSONAS,
        'afiliados' => Menu::AFILIADOS_BASES,
        'usuarios' => Menu::USUARIOS,
        'personeros' => Menu::PERSONEROS,
    ];

    /**
     * Bloquea el acceso a la ruta cuando el usuario no tiene el menú asignado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        if ($this->permitida((string) $request->route()?->getName(), $usuario, $request)) {
            return $next($request);
        }

        abort(403, 'No tiene acceso a este módulo. Contacte al administrador.');
    }

    /**
     * Indica si el usuario puede acceder a la ruta solicitada.
     */
    private function permitida(string $ruta, Usuario $usuario, Request $request): bool
    {
        if ($usuario->esAdmin()) {
            return true;
        }

        // Registro de su propio conteo: todo personero puede acceder aunque no
        // tenga el menú asignado (el controlador limita al suyo).
        if (in_array($ruta, ['votos.registrar', 'votos.guardar'], true)) {
            $personaId = (int) $usuario->persona_id;

            if ($personaId && Personero::where('persona_id', $personaId)->exists()) {
                return true;
            }
        }

        // Cada usuario puede consultar y actualizar su propia cuenta.
        if (in_array($ruta, ['usuarios.edit', 'usuarios.update'], true)) {
            $propietario = $request->route('usuario');

            if ($propietario && (int) $propietario->usuario_id === (int) $usuario->usuario_id) {
                return true;
            }
        }

        // Registro de personas: se valida el módulo desde el que se originó.
        if (in_array($ruta, ['personas.create', 'personas.store'], true)) {
            $origen = (string) $request->input('origen', 'personas');

            if ($usuario->tieneAccesoDescripcion(self::ORIGENES[$origen] ?? Menu::PERSONAS)) {
                return true;
            }
        }

        $menus = self::RUTAS[$ruta] ?? null;

        if ($menus === null) {
            return true;
        }

        foreach ((array) $menus as $menu) {
            if ($usuario->tieneAccesoDescripcion($menu)) {
                return true;
            }
        }

        return false;
    }
}
