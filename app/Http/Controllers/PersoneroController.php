<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\Afiliado;
use App\Models\Menu;
use App\Models\Mesa;
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
        $afiliadoSeleccionado = old('afiliado_id')
            ? Afiliado::with('persona')->where('afiliado_id', old('afiliado_id'))->first()
            : null;
        $personaSeleccionada = $afiliadoSeleccionado?->persona;

        return view('personeros.create', compact('personero', 'mesas', 'afiliadoSeleccionado', 'personaSeleccionada'));
    }

    /**
     * Busca un afiliado por su DNI para asignarlo como personero (AJAX).
     */
    public function buscarPersona(Request $request)
    {
        $dni = preg_replace('/\D/', '', (string) $request->query('dni', ''));

        if ($dni === '') {
            return response()->json(['mensaje' => 'Ingrese un DNI para buscar al afiliado.'], 422);
        }

        $afiliado = Afiliado::with('persona')
            ->whereHas('persona', fn ($q) => $q->whereRaw(
                "REPLACE(REPLACE(REPLACE(TRIM(dni), '.', ''), '-', ''), ' ', '') = ?",
                [$dni]
            ))->first();

        if (! $afiliado) {
            return response()->json(['mensaje' => 'No se encontró ningún afiliado con el DNI ingresado.'], 404);
        }

        if ($afiliado->personeros()->exists()) {
            return response()->json([
                'mensaje' => 'El afiliado '.$afiliado->persona->apellidoNombre().' (DNI: '.$afiliado->persona->dni.') ya está registrado como personero.',
            ], 409);
        }

        return response()->json([
            'afiliado_id' => $afiliado->afiliado_id,
            'persona_id' => $afiliado->persona_id,
            'dni' => $afiliado->persona->dni,
            'nombre_completo' => $afiliado->persona->apellidoNombre(),
            'telefono' => $afiliado->persona->telefono,
        ]);
    }

    /**
     * Afiliados sin registro de personero para el modal (paginado en el servidor).
     */
    public function personas()
    {
        $query = Afiliado::query()
            ->with('persona')
            ->whereDoesntHave('personeros')
            ->join('personas', 'personas.persona_id', '=', 'afiliados.persona_id')
            ->select(['afiliados.afiliado_id', 'afiliados.persona_id', 'personas.dni', 'personas.nombres', 'personas.primer_apellido', 'personas.segundo_apellido', 'personas.telefono']);

        return DataTables::eloquent($query)
            ->addColumn('persona', fn (Afiliado $afiliado) => $afiliado->persona?->apellidoNombre())
            ->toJson();
    }

    /**
     * Almacena un nuevo personero y crea su cuenta de usuario.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $afiliado = Afiliado::findOrFail((int) $data['afiliado_id']);
        $this->verificarPersoneroActivo($afiliado);
        $this->verificarMesaDisponible((int) $data['mesa_id']);

        DB::transaction(function () use ($data, $request, $afiliado) {
            $personero = new Personero();
            $personero->afiliado_id = $afiliado->afiliado_id;
            $personero->persona_id = $afiliado->persona_id;
            $personero->mesa_id = $data['mesa_id'];
            $personero->autor_id = Auth::id();
            $personero->editor_id = Auth::id();
            $personero->save();

            // Solo se crea la cuenta si el check está marcado.
            if ($request->boolean('crear_usuario')) {
                $this->crearUsuario($personero);
            }
        });

        $mensaje = 'Personero registrado correctamente.';
        if ($request->boolean('crear_usuario')) {
            $mensaje .= ' Se creó su usuario con el DNI como usuario y contraseña.';
        }

        return redirect()->route('personeros.index')->with('success', $mensaje);
    }

    /**
     * Formulario para editar un personero (la persona no se modifica).
     */
    public function edit(Personero $personero)
    {
        $mesas = $this->mesasOrdenadas();
        $afiliadoSeleccionado = $personero->afiliado ?? Afiliado::with('persona')->find($personero->afiliado_id);
        $personaSeleccionada = $afiliadoSeleccionado?->persona ?? $personero->persona;

        return view('personeros.edit', compact('personero', 'mesas', 'afiliadoSeleccionado', 'personaSeleccionada'));
    }

    /**
     * Actualiza la mesa asignada al personero.
     */
    public function update(Request $request, Personero $personero)
    {
        $data = $this->validar($request, $personero);
        $this->verificarMesaDisponible((int) $data['mesa_id'], $personero->personero_id);

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
     * Se incluye `personeros_count` para deshabilitar las mesas que ya tienen apoderado.
     */
    private function mesasOrdenadas()
    {
        return Mesa::with('centro')
            ->withCount('personeros')
            ->orderBy('descripcion')
            ->get();
    }

    /**
     * Reglas de validación al crear o actualizar un personero.
     */
    private function validar(Request $request, ?Personero $personero = null)
    {
        // Al editar, el afiliado permanece fijo: se toma el del registro actual.
        if ($personero) {
            $request->merge(['afiliado_id' => $personero->afiliado_id ?? $personero->persona_id]);
        }

        return $request->validate([
            'afiliado_id' => 'required|integer|exists:afiliados,afiliado_id',
            'mesa_id' => 'required|integer|exists:mesas,mesa_id',
        ], [], [
            'afiliado_id' => 'afiliado',
            'mesa_id' => 'mesa',
        ]);
    }

    /**
     * Verifica que el afiliado no tenga ya un registro de personero activo.
     */
    private function verificarPersoneroActivo(Afiliado $afiliado): void
    {
        $personero = Personero::with(['persona', 'mesa'])->where('afiliado_id', $afiliado->afiliado_id)->first();

        if (! $personero) {
            return;
        }

        $persona = $personero->persona?->apellidoNombre() ?? 'El afiliado';
        $mesa = $personero->mesa?->etiqueta() ?? 'otra mesa';

        throw ValidationException::withMessages([
            'afiliado_id' => $persona.' ya está registrado como personero de la '.$mesa.'.',
        ])->redirectTo(route('personeros.create'));
    }

    /**
     * Verifica que la mesa no tenga ya un personero (apoderado) asignado:
     * cada mesa solo puede tener uno.
     */
    private function verificarMesaDisponible(int $mesaId, ?int $personeroId = null): void
    {
        $personero = Personero::with(['persona', 'mesa'])
            ->where('mesa_id', $mesaId)
            ->when($personeroId, fn ($query) => $query->where('personero_id', '!=', $personeroId))
            ->first();

        if (! $personero) {
            return;
        }

        $mesa = $personero->mesa?->etiqueta() ?? 'esa mesa';
        $persona = $personero->persona?->apellidoNombre() ?? 'Otra persona';

        throw ValidationException::withMessages([
            'mesa_id' => 'La '.$mesa.' ya tiene el personero '.$persona.'. Solo puede haber un apoderado por mesa.',
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