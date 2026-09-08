<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\Menu;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Listado de usuarios del sistema.
     */
    public function index()
    {
        $usuarios = Usuario::with('persona')
            ->withCount('accesos')
            ->latest('usuario_id')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Formulario para registrar un nuevo usuario.
     */
    public function create()
    {
        $menu_ids = [];
        $menus = $this->menusJerarquicos();
        $usuario = new Usuario();

        // Recupera la persona elegida si el formulario fue enviado y falló la validación.
        $personaSeleccionada = old('persona_id')
            ? Persona::where('persona_id', old('persona_id'))->first()
            : null;

        return view('usuarios.create', compact('usuario', 'menus', 'menu_ids', 'personaSeleccionada'));
    }

    /**
     * Busca una persona por su DNI para asignarla como nuevo usuario (AJAX).
     *
     * Devuelve JSON con la persona encontrada o el motivo por el cual no se puede usar.
     */
    public function buscarPersonaPorDni(Request $request)
    {
        $dni = preg_replace('/\D/', '', (string) $request->query('dni', ''));

        if ($dni === '') {
            return response()->json(['mensaje' => 'Ingrese un DNI para buscar la persona.'], 422);
        }

        $persona = Persona::whereRaw(
            "REPLACE(REPLACE(REPLACE(TRIM(dni), '.', ''), '-', ''), ' ', '') = ?",
            [$dni]
        )->first();

        if (! $persona) {
            return response()->json(['mensaje' => 'No se encontró ninguna persona con el DNI ingresado.'], 404);
        }

        if ($persona->usuario()->exists()) {
            return response()->json([
                'mensaje' => 'La persona '.$persona->apellidoNombre().' (DNI: '.$persona->dni.') ya tiene una cuenta de usuario.',
            ], 409);
        }

        return response()->json([
            'persona_id' => $persona->persona_id,
            'dni' => $persona->dni,
            'nombre_completo' => $persona->apellidoNombre(),
        ]);
    }

    /**
     * Almacena un nuevo usuario y registra sus accesos (menús).
     */
    public function store(Request $request)
    {
        $data = $this->validarCreacion($request);

        DB::transaction(function () use ($data) {
            $usuario = new Usuario();
            $usuario->persona_id = $data['persona_id'];
            $usuario->clave = Hash::make($data['clave']);
            $usuario->autor_id = Auth::id();
            $usuario->editor_id = Auth::id();
            $usuario->save();

            $this->guardarAccesos($usuario, $data['menu_ids'] ?? []);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente.');
    }

    /**
     * Formulario para editar un usuario existente.
     */
    public function edit(Usuario $usuario)
    {
        $menu_ids = $usuario->accesos()->pluck('menu_id')->toArray();
        $menus = $this->menusJerarquicos();

        return view('usuarios.edit', compact('usuario', 'menus', 'menu_ids'));
    }

    /**
     * Actualiza los datos y los accesos (menús) de un usuario.
     */
    public function update(Request $request, Usuario $usuario)
    {
        $data = $this->validarUpdate($request, $usuario);

        DB::transaction(function () use ($usuario, $data) {
            $usuario->editor_id = Auth::id();

            if (! empty($data['clave'])) {
                $usuario->clave = Hash::make($data['clave']);
            }

            $usuario->save();

            $this->guardarAccesos($usuario, $data['menu_ids'] ?? []);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un usuario y sus accesos.
     */
    public function destroy(Usuario $usuario)
    {
        DB::transaction(function () use ($usuario) {
            // Libera el índice único (usuario_id, menu_id) antes del borrado lógico.
            $usuario->accesos()->forceDelete();
            $usuario->delete();
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Menús principales ordenados con sus submenús.
     */
    private function menusJerarquicos()
    {
        return Menu::with(['hijos' => fn ($query) => $query->orderBy('descripcion')])
            ->whereNull('menu_padre_id')
            ->orderBy('descripcion')
            ->get();
    }

    /**
     * Registra los accesos (asignación de menús) de un usuario.
     */
    private function guardarAccesos(Usuario $usuario, array $menuIds)
    {
        $menuIds = array_map('intval', (array) $menuIds);
        $menuIds = array_values(array_unique(array_filter($menuIds, fn ($id) => $id > 0)));

        $usuario->accesos()->forceDelete();

        foreach ($menuIds as $menuId) {
            Acceso::create([
                'usuario_id' => $usuario->usuario_id,
                'menu_id' => $menuId,
                'autor_id' => Auth::id(),
                'editor_id' => Auth::id(),
            ]);
        }
    }
/**
     * Reglas de validación al crear un usuario.
     */
    private function validarCreacion(Request $request)
    {
        return $request->validate([
            'persona_id' => 'required|integer|exists:personas,persona_id|unique:usuarios,persona_id',
            'clave' => 'required|string|min:6',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'integer|exists:menu,menu_id',
        ], [], [
            'persona_id' => 'persona',
            'clave' => 'contraseña',
        ]);
    }

    /**
     * Reglas de validación al actualizar un usuario.
     */
    private function validarUpdate(Request $request, Usuario $usuario)
    {
        return $request->validate([
            'persona_id' => 'nullable|integer|exists:personas,persona_id|unique:usuarios,persona_id,' . $usuario->usuario_id . ',usuario_id',
            'clave' => 'nullable|string|min:6',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'integer|exists:menu,menu_id',
        ], [], [
            'persona_id' => 'persona',
            'clave' => 'contraseña',
        ]);
    }
}
