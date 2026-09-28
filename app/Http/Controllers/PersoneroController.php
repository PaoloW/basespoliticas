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
     * La persona es el único dato de origen: se puede llegar con ?dni= (búsqueda
     * fallida) o con ?persona_id= (retorno del alta de una persona nueva).
     */
    public function create(Request $request)
    {
        $personero = new Personero();
        $mesas = $this->mesasOrdenadas();
        $personaSeleccionada = $this->personaDeRetorno($request);

        return view('personeros.create', compact('personero', 'mesas', 'personaSeleccionada'));
    }

    /**
     * Busca una persona por su DNI para asignarla como personero (AJAX).
     * Devuelve además su afiliación si la tiene (es opcional).
     */
    public function buscarPersona(Request $request)
    {
        $dni = $this->normalizarDni($request->query('dni'));

        if ($dni === '') {
            return response()->json(['mensaje' => 'Ingrese un DNI para buscar la persona.'], 422);
        }

        $persona = $this->personaPorDni($dni);

        if (! $persona) {
            return response()->json(['mensaje' => 'No se encontró ninguna persona con el DNI ingresado.'], 404);
        }

        if ($persona->esPersonero()) {
            $personero = Personero::with('mesa')->where('persona_id', $persona->persona_id)->first();
            $mesa = $personero?->mesa?->etiqueta() ?? 'otra mesa';

            return response()->json([
                'mensaje' => 'La persona '.$persona->apellidoNombre().' (DNI: '.$persona->dni.') ya está registrada como personero de la '.$mesa.'.',
            ], 409);
        }

        return response()->json($this->datosPersona($persona));
    }

    /**
     * Personas sin personero vigente para el modal (paginado en el servidor).
     * Se muestra su afiliación cuando la tienen; es opcional.
     */
    public function personas()
    {
        $query = Persona::query()
            ->with(['afiliados.base', 'afiliados.cargo'])
            ->whereDoesntHave('personeros')
            ->select(['persona_id', 'dni', 'nombres', 'primer_apellido', 'segundo_apellido', 'telefono']);

        return DataTables::eloquent($query)
            ->addColumn('persona', fn (Persona $persona) => $persona->apellidoNombre())
            ->addColumn('afiliacion', fn (Persona $persona) => $persona->descripcionAfiliacion())
            ->filterColumn('persona', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('nombres', 'like', "%{$keyword}%")
                        ->orWhere('primer_apellido', 'like', "%{$keyword}%")
                        ->orWhere('segundo_apellido', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('afiliacion', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('afiliados.base', fn ($base) => $base->where('descripcion', 'like', "%{$keyword}%"))
                        ->orWhereHas('afiliados.cargo', fn ($cargo) => $cargo->where('descripcion', 'like', "%{$keyword}%"));
                });
            })
            ->toJson();
    }

    /**
     * Almacena un nuevo personero y, opcionalmente, crea su cuenta de usuario.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $persona = Persona::findOrFail((int) $data['persona_id']);
        $this->verificarPersoneroActivo($persona);
        $this->verificarMesaDisponible((int) $data['mesa_id']);

        DB::transaction(function () use ($data, $request, $persona) {
            $personero = new Personero();
            $personero->persona_id = $persona->persona_id;
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
        $personaSeleccionada = $personero->persona;

        return view('personeros.edit', compact('personero', 'mesas', 'personaSeleccionada'));
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
     * Persona a mostrar en el formulario: por ?persona_id= o por ?dni= (retorno
     * del alta de una persona nueva o de una búsqueda fallida).
     */
    private function personaDeRetorno(Request $request): ?Persona
    {
        $personaId = old('persona_id', $request->query('persona_id'));

        if ($personaId) {
            $persona = Persona::with(['afiliados.base', 'afiliados.cargo'])->find($personaId);

            if ($persona) {
                return $persona;
            }
        }

        $dni = $this->normalizarDni(old('dni_personero', $request->query('dni')));

        return $dni === '' ? null : $this->personaPorDni($dni);
    }

    /**
     * Datos de la persona para el buscador por DNI (la afiliación es opcional).
     */
    private function datosPersona(Persona $persona): array
    {
        return [
            'persona_id' => $persona->persona_id,
            'dni' => $persona->dni,
            'nombre_completo' => $persona->apellidoNombre(),
            'telefono' => $persona->telefono,
            'afiliacion' => $persona->descripcionAfiliacion(),
        ];
    }

    /**
     * DNI normalizado (solo dígitos).
     */
    private function normalizarDni(mixed $dni): string
    {
        return preg_replace('/\D/', '', (string) $dni);
    }

    /**
     * Persona por DNI, tolerando puntos, guiones y espacios en el valor guardado.
     */
    private function personaPorDni(string $dni): ?Persona
    {
        return Persona::with(['afiliados.base', 'afiliados.cargo'])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(TRIM(dni), '.', ''), '-', ''), ' ', '') = ?",
                [$dni]
            )->first();
    }

    /**
     * Verifica que la persona no tenga ya un registro de personero vigente.
     */
    private function verificarPersoneroActivo(Persona $persona): void
    {
        $personero = Personero::with('mesa')->where('persona_id', $persona->persona_id)->first();

        if (! $personero) {
            return;
        }

        $mesa = $personero->mesa?->etiqueta() ?? 'otra mesa';

        throw ValidationException::withMessages([
            'persona_id' => $persona->apellidoNombre().' ya está registrado como personero de la '.$mesa.'.',
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