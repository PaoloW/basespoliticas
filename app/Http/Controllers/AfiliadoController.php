<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Base;
use App\Models\Cargo;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AfiliadoController extends Controller
{
    /**
     * Listado de afiliaciones activas.
     */
    public function index()
    {
        $afiliados = Afiliado::with(['persona', 'base', 'cargo'])
            ->latest('afiliado_id')
            ->get();

        return view('afiliados.index', compact('afiliados'));
    }

    /**
     * Formulario para registrar una nueva afiliación.
     */
    public function create()
    {
        $afiliado = new Afiliado();
        $cargos = $this->cargosOrdenados();
        $bases = $this->basesOrdenadas();
        $personaSeleccionada = old('persona_id') ? Persona::where('persona_id', old('persona_id'))->first() : null;

        return view('afiliados.create', compact('afiliado', 'cargos', 'bases', 'personaSeleccionada'));
    }

    /**
     * Almacena una nueva afiliación. Una persona solo puede tener una
     * afiliación activa; para cambiar de base primero se debe anular.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $this->verificarAfiliacionActiva((int) $data['persona_id']);

        DB::transaction(function () use ($data) {
            $afiliado = new Afiliado();
            $afiliado->persona_id = $data['persona_id'];
            $afiliado->base_id = $data['base_id'];
            $afiliado->cargo_id = $data['cargo_id'];
            $afiliado->autor_id = Auth::id();
            $afiliado->editor_id = Auth::id();
            $afiliado->save();
        });

        return redirect()->route('afiliados.index')->with('success', 'Afiliación registrada correctamente.');
    }

    /**
     * Formulario para editar una afiliación (la persona no se modifica).
     */
    public function edit(Afiliado $afiliado)
    {
        $cargos = $this->cargosOrdenados();
        $bases = $this->basesOrdenadas();
        $personaSeleccionada = $afiliado->persona;

        return view('afiliados.edit', compact('afiliado', 'cargos', 'bases', 'personaSeleccionada'));
    }

    /**
     * Actualiza la base y el cargo de una afiliación.
     */
    public function update(Request $request, Afiliado $afiliado)
    {
        $data = $this->validar($request, $afiliado);

        DB::transaction(function () use ($afiliado, $data) {
            $afiliado->base_id = $data['base_id'];
            $afiliado->cargo_id = $data['cargo_id'];
            $afiliado->editor_id = Auth::id();
            $afiliado->save();
        });

        return redirect()->route('afiliados.index')->with('success', 'Afiliación actualizada correctamente.');
    }

    /**
     * Anula (soft delete) una afiliación para liberar a la persona y permitir
     * su afiliación a otra base.
     */
    public function destroy(Afiliado $afiliado)
    {
        DB::transaction(function () use ($afiliado) {
            $afiliado->editor_id = Auth::id();
            $afiliado->delete();
        });

        return redirect()->route('afiliados.index')->with('success', 'Afiliación anulada correctamente.');
    }

    /**
     * Busca una persona por su DNI para afiliarla (AJAX).
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

        if ($persona->afiliados()->exists()) {
            $base = $persona->afiliados()->with('base')->first()?->base?->descripcion ?? 'otra base';

            return response()->json([
                'mensaje' => 'La persona '.$persona->apellidoNombre().' (DNI: '.$persona->dni.') ya está afiliada a la base '.$base.'. Anule la afiliación antes de registrar una nueva.',
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
     * Personas sin afiliación activa para el modal (paginado en el servidor).
     */
    public function personas()
    {
        $query = Persona::query()
            ->whereDoesntHave('afiliados')
            ->select(['persona_id', 'dni', 'nombres', 'primer_apellido', 'segundo_apellido', 'telefono']);

        return DataTables::eloquent($query)
            ->addColumn('persona', fn (Persona $persona) => $persona->apellidoNombre())
            ->toJson();
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Cargos ordenados por descripción, para el select del formulario.
     */
    private function cargosOrdenados()
    {
        return Cargo::orderBy('descripcion')->get();
    }

    /**
     * Bases ordenadas por descripción con su partido, para el select del formulario.
     */
    private function basesOrdenadas()
    {
        return Base::with('partido')->orderBy('descripcion')->get();
    }

    /**
     * Reglas de validación al crear o actualizar una afiliación.
     */
    private function validar(Request $request, ?Afiliado $afiliado = null)
    {
        // Al editar, la persona permanece fija: se toma la del registro actual.
        if ($afiliado) {
            $request->merge(['persona_id' => $afiliado->persona_id]);
        }

        return $request->validate([
            'persona_id' => 'required|integer|exists:personas,persona_id',
            'base_id' => 'required|integer|exists:bases,base_id',
            'cargo_id' => 'required|integer|exists:cargos,cargo_id',
        ], [], [
            'persona_id' => 'persona',
            'base_id' => 'base',
            'cargo_id' => 'cargo',
        ]);
    }

    /**
     * Verifica que la persona no tenga una afiliación activa.
     */
    private function verificarAfiliacionActiva(int $personaId): void
    {
        $afiliacion = Afiliado::with(['persona', 'base'])->where('persona_id', $personaId)->first();

        if (! $afiliacion) {
            return;
        }

        $persona = $afiliacion->persona?->apellidoNombre() ?? 'La persona';
        $base = $afiliacion->base?->descripcion ?? 'otra base';

        throw ValidationException::withMessages([
            'persona_id' => $persona.' ya está afiliada a la base '.$base.'. Anule la afiliación antes de registrar una nueva.',
        ])->redirectTo(route('afiliados.create'));
    }
}
