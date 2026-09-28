<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Persona;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VotoController extends Controller
{
    /**
     * Listado de votos registrados por partido y persona.
     */
    public function index()
    {
        $votos = Voto::with(['partido', 'persona'])
            ->latest('voto_id')
            ->get();

        return view('votos.index', compact('votos'));
    }

    /**
     * Formulario para registrar votos.
     */
    public function create()
    {
        $voto = new Voto();
        $partidos = $this->partidosOrdenados();
        $personas = $this->personasOrdenadas();

        return view('votos.create', compact('voto', 'partidos', 'personas'));
    }

    /**
     * Almacena los votos del partido y la persona seleccionados.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        $existente = Voto::withTrashed()
            ->where('partido_id', $data['partido_id'])
            ->where('persona_id', $data['persona_id'])
            ->first();

        // Un partido solo tiene un registro de votos por persona.
        if ($existente && ! $existente->trashed()) {
            $nombre = $existente->persona?->apellidoNombre() ?? 'Sin nombre';
            $partido = $existente->partido?->nombre ?? 'Sin partido';

            throw ValidationException::withMessages([
                'persona_id' => 'La persona '.$nombre.' ya tiene votos registrados para el partido '.$partido.'.',
            ])->redirectTo(route('votos.create'));
        }

        DB::transaction(function () use ($data, $existente) {
            // Un registro eliminado antes se reactiva con los votos actuales.
            $voto = $existente ?? new Voto();

            if ($existente) {
                $voto->restore();
            } else {
                $voto->partido_id = $data['partido_id'];
                $voto->persona_id = $data['persona_id'];
                $voto->autor_id = Auth::id();
            }

            $voto->votos = $data['votos'];
            $voto->editor_id = Auth::id();
            $voto->save();
        });

        return redirect()->route('votos.index')->with('success', 'Votos registrados correctamente.');
    }

    /**
     * Formulario para actualizar los votos de un registro.
     */
    public function edit(Voto $voto)
    {
        return view('votos.edit', compact('voto'));
    }

    /**
     * Actualiza la cantidad de votos actuales.
     */
    public function update(Request $request, Voto $voto)
    {
        $data = $this->validar($request, false);

        DB::transaction(function () use ($voto, $data) {
            $voto->votos = $data['votos'];
            $voto->editor_id = Auth::id();
            $voto->save();
        });

        return redirect()->route('votos.index')->with('success', 'Votos actualizados correctamente.');
    }

    /**
     * Elimina (soft delete) un registro de votos.
     */
    public function destroy(Voto $voto)
    {
        DB::transaction(function () use ($voto) {
            $voto->delete();
        });

        return redirect()->route('votos.index')->with('success', 'Registro de votos eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Partidos ordenados por nombre, para el select del formulario.
     */
    private function partidosOrdenados()
    {
        return Partido::orderBy('nombre')->get();
    }

    /**
     * Personas ordenadas por apellidos y nombres, para el select del formulario.
     */
    private function personasOrdenadas()
    {
        return Persona::orderBy('primer_apellido')
            ->orderBy('segundo_apellido')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Reglas de validación del registro (partido, persona y votos) y de la
     * edición (solo los votos, porque el partido y la persona no se modifican).
     */
    private function validar(Request $request, bool $conPartidoYPersona = true)
    {
        $reglas = ['votos' => 'required|integer|min:0'];

        if ($conPartidoYPersona) {
            $reglas = [
                'partido_id' => 'required|integer|exists:partidos,partido_id',
                'persona_id' => 'required|integer|exists:personas,persona_id',
            ] + $reglas;
        }

        return $request->validate($reglas, [], [
            'partido_id' => 'partido',
            'persona_id' => 'persona',
            'votos' => 'votos',
        ]);
    }
}
