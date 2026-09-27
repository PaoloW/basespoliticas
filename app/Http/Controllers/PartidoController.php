<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartidoController extends Controller
{
    /**
     * Listado de partidos políticos.
     */
    public function index()
    {
        $partidos = Partido::orderBy('nombre')
            ->get();

        return view('partidos.index', compact('partidos'));
    }

    /**
     * Formulario para registrar un nuevo partido.
     */
    public function create()
    {
        $partido = new Partido();

        return view('partidos.create', compact('partido'));
    }

    /**
     * Almacena un nuevo partido.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $partido = new Partido();
            $partido->nombre = $data['nombre'];
            $partido->autor_id = Auth::id();
            $partido->editor_id = Auth::id();
            $partido->save();
        });

        return redirect()->route('partidos.index')->with('success', 'Partido registrado correctamente.');
    }

    /**
     * Formulario para editar un partido existente.
     */
    public function edit(Partido $partido)
    {
        return view('partidos.edit', compact('partido'));
    }

    /**
     * Actualiza los datos de un partido.
     */
    public function update(Request $request, Partido $partido)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($partido, $data) {
            $partido->nombre = $data['nombre'];
            $partido->editor_id = Auth::id();
            $partido->save();
        });

        return redirect()->route('partidos.index')->with('success', 'Partido actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un partido.
     */
    public function destroy(Partido $partido)
    {
        DB::transaction(function () use ($partido) {
            $partido->delete();
        });

        return redirect()->route('partidos.index')->with('success', 'Partido eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar un partido.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
        ], [], [
            'nombre' => 'nombre del partido',
        ]);
    }
}
