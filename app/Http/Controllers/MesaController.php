<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use App\Models\Mesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MesaController extends Controller
{
    /**
     * Listado de mesas de votación.
     */
    public function index()
    {
        $mesas = Mesa::with(['centro' => fn ($query) => $query->orderBy('descripcion')])
            ->withCount('personeros')
            ->orderBy('descripcion')
            ->get();

        return view('mesas.index', compact('mesas'));
    }

    /**
     * Formulario para registrar una nueva mesa de votación.
     */
    public function create()
    {
        $centros = Centro::orderBy('descripcion')->get();
        $mesa = new Mesa();

        return view('mesas.create', compact('mesa', 'centros'));
    }

    /**
     * Almacena una nueva mesa de votación.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $mesa = new Mesa();
            $mesa->descripcion = $data['descripcion'] ?? null;
            $mesa->centro_id = $data['centro_id'];
            $mesa->votantes = $data['votantes'] ?? null;
            $mesa->autor_id = Auth::id();
            $mesa->editor_id = Auth::id();
            $mesa->save();
        });

        return redirect()->route('mesas.index')->with('success', 'Mesa de votación registrada correctamente.');
    }

    /**
     * Formulario para editar una mesa de votación existente.
     */
    public function edit(Mesa $mesa)
    {
        $centros = Centro::orderBy('descripcion')->get();

        return view('mesas.edit', compact('mesa', 'centros'));
    }

    /**
     * Actualiza los datos de una mesa de votación.
     */
    public function update(Request $request, Mesa $mesa)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($mesa, $data) {
            $mesa->descripcion = $data['descripcion'] ?? null;
            $mesa->centro_id = $data['centro_id'];
            $mesa->votantes = $data['votantes'] ?? null;
            $mesa->editor_id = Auth::id();
            $mesa->save();
        });

        return redirect()->route('mesas.index')->with('success', 'Mesa de votación actualizada correctamente.');
    }

    /**
     * Elimina (soft delete) una mesa de votación.
     */
    public function destroy(Mesa $mesa)
    {
        DB::transaction(function () use ($mesa) {
            $mesa->delete();
        });

        return redirect()->route('mesas.index')->with('success', 'Mesa de votación eliminada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar una mesa de votación.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'descripcion' => 'nullable|integer|min:1',
            'centro_id' => 'required|integer|exists:centros,centro_id',
            'votantes' => 'nullable|integer|min:0',
        ], [], [
            'descripcion' => 'número de mesa',
            'centro_id' => 'centro de votación',
            'votantes' => 'votantes',
        ]);
    }
}