<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\Menu;
use App\Models\Mesa;
use App\Models\Persona;
use App\Models\Personero;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PersoneroController extends Controller
{
    /**
     * Listado de personeros registrados.
     */
    public function index()
    {
        $personeros = Personero::with(['persona.usuario', 'mesa.centro'])
            ->withCount('votos')
            ->latest('personero_id')
            ->get();

        return view('personeros.index', compact('personeros'));
    }

    /**
     * Formulario para registrar un nuevo personero.
     */
    public function create()
    {
        $personero = new Personero();
        $mesas = $this->mesasOrdenadas();
        $personaSeleccionada = old('persona_id')
            ? Persona::where('persona_id', old('persona_id'))->first()
            : null;

        return view('personeros.create', compact('personero', 'mesas', 'personaSeleccionada'));
    }

    /**
     * Busca una persona por su DNI para asignarla como personero (AJAX).
     */
    public function buscarPersona(Request $request)
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

        if ($persona->personeros()->exists()) {
            return response()->json([
                'mensaje' => 'La persona '.$persona->apellidoNombre().' (DNI: '.$persona->dni.') ya está registrada como personero.',
            ], 409);
        }

        return response()->json([
            'persona_id' => $persona->persona_id,
            'dni' => $persona->dni,
            'nombre_completo' => $persona->apellidoNombre(),
            'telefono' => $persona->telefono,
        ]);
    }

    /**
     * Personas sin registro de personero para el modal (paginado en el servidor).
     */
    public function personas()
    {
        $query = Persona::query()
            ->whereDoesntHave('personeros')
            ->select(['persona_id', 'dni', 'nombres', 'primer_apellido', 'segundo_apellido', 'telefono']);

        return DataTables::eloquent($query)
            ->addColumn('persona', fn (Persona $persona) => $persona->apellidoNombre())
            ->toJson();
    }

    /**
     * Almacena un nuevo personero y crea su cuenta de usuario.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $this->verificarPersoneroActivo((int) $data['persona_id']);

        DB::transaction(function () use ($data) {
            $personero = new Personero();
            $personero->persona_id = $data['persona_id'];
            $personero->mesa_id = $data['mesa_id'];
            $personero->autor_id = Auth::id();
            $personero->editor_id = Auth::id();
            $personero->save();

            // Cada personero cuenta con su propia cuenta: DNI como usuario y contraseña.
            $this->crearUsuario($personero);
        });

        return redirect()->route('personeros.index')
            ->with('success', 'Personero registrado correctamente. Se creó su usuario con el DNI como usuario y contraseña.');
    }

    /**
     * Formulario para editar un personero (la persona no se modifica).
     */
    public function edit(Personero $personero)
    {
        $mesas = $this->mesasOrdenadas();
        $personaSeleccionada = $personero->persona;

        return view('personeros.edit', compact('personero', 'mesas', 'personaSeleccionada'));
    }

    /**
     * Actualiza la mesa asignada al personero.
     */
    public function update(Request $request, Personero $personero)
    {
        $data = $this->validar($request, $personero);

        DB::transaction(function () use ($personero, $data) {
            $personero->mesa_id = $data['mesa_id'];
            $personero->editor_id = Auth::id();
            $personero->save();
        });

        return redirect()->route('personeros.index')->with('success', 'Personero actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un personero.
     */
    public function destroy(Personero $personero)
    {
        DB::transaction(function () use ($personero) {
            $personero->editor_id = Auth::id();
            $personero->delete();
        });

        return redirect()->route('personeros.index')->with('success', 'Personero eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Mesas ordenadas por descripción con su centro, para el select del formulario.
     */
    private function mesasOrdenadas()
    {
        return Mesa::with('centro')->orderBy('descripcion')->get();
    }

    /**
     * Reglas de validación al crear o actualizar un personero.
     */
    private function validar(Request $request, ?Personero $personero = null)
    {
        // Al editar, la persona permanece fija: se toma la del registro actual.
        if ($personero) {
            $request->merge(['persona_id' => $personero->persona_id]);
        }

        return $request->validate([
            'persona_id' => 'required|integer|exists:personas,persona_id',
            'mesa_id' => 'required|integer|exists:mesas,mesa_id',
        ], [], [
            'persona_id' => 'persona',
            'mesa_id' => 'mesa',
        ]);
    }

    /**
     * Verifica que la persona no tenga ya un registro de personero activo.
     */
    private function verificarPersoneroActivo(int $personaId): void
    {
        $personero = Personero::with(['persona', 'mesa'])->where('persona_id', $personaId)->first();

        if (! $personero) {
            return;
        }

        $persona = $personero->persona?->apellidoNombre() ?? 'La persona';
        $mesa = $personero->mesa?->etiqueta() ?? 'otra mesa';

        throw ValidationException::withMessages([
            'persona_id' => $persona.' ya está registrada como personero de la '.$mesa.'.',
        ])->redirectTo(route('personeros.create'));
    }

    /**
     * Crea (o reutiliza) la cuenta del personero usando el DNI como usuario y
     * contraseña, y le asigna por defecto el acceso "Registrar conteo de votos".
     */
    private function crearUsuario(Personero $personero): void
    {
        $persona = $personero->persona;

        if (! $persona) {
            return;
        }

        $dni = $persona->dniNormalizado() ?: (string) $persona->dni;
        $usuario = $persona->usuario()->withTrashed()->first();

        if ($usuario) {
            if ($usuario->trashed()) {
                $usuario->restore();
            }
        } else {
            $usuario = new Usuario();
            $usuario->persona_id = $persona->persona_id;
            $usuario->clave = Hash::make($dni);
            $usuario->autor_id = Auth::id();
            $usuario->editor_id = Auth::id();
            $usuario->save();
        }

        $this->asignarMenuPorDefecto($usuario);
    }

    /**
     * Asigna el menú "Registrar conteo de votos" al usuario, reactivándolo si
     * estaba dado de baja.
     */
    private function asignarMenuPorDefecto(Usuario $usuario): void
    {
        $menu = Menu::where('descripcion', Menu::REGISTRAR_CONTEOS)->first();

        if (! $menu) {
            return;
        }

        $acceso = $usuario->accesos()->withTrashed()->where('menu_id', $menu->menu_id)->first();

        if ($acceso) {
            if ($acceso->trashed()) {
                $acceso->restore();
            }
            $acceso->editor_id = Auth::id();
            $acceso->save();

            return;
        }

        Acceso::create([
            'usuario_id' => $usuario->usuario_id,
            'menu_id' => $menu->menu_id,
            'autor_id' => Auth::id(),
            'editor_id' => Auth::id(),
        ]);
    }
}