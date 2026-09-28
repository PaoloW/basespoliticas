<?php

namespace App\Http\Controllers;

use App\Models\Centro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CentroController extends Controller
{
    /**
     * Listado de centros de votación.
     */
    public function index()
    {
        $centros = Centro::with(['mesas' => fn ($query) => $query->orderBy('descripcion')])
            ->orderBy('descripcion')
            ->get();

        return view('centros.index', compact('centros'));
    }

    /**
     * Formulario para registrar un nuevo centro de votación.
     */
    public function create()
    {
        $centro = new Centro();

        return view('centros.create', compact('centro'));
    }

    /**
     * Almacena un nuevo centro de votación.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $centro = new Centro();
            $centro->descripcion = $data['descripcion'];
            $centro->ubicacion = $data['ubicacion'] ?? null;
            $centro->distrito = $data['distrito'] ?? null;
            $centro->autor_id = Auth::id();
            $centro->editor_id = Auth::id();
            $centro->save();
        });

        return redirect()->route('centros.index')->with('success', 'Centro de votación registrado correctamente.');
    }

    /**
     * Formulario para editar un centro de votación existente.
     */
    public function edit(Centro $centro)
    {
        return view('centros.edit', compact('centro'));
    }

    /**
     * Actualiza los datos de un centro de votación.
     */
    public function update(Request $request, Centro $centro)
    {
        $data = $this->validar($request, $centro);

        DB::transaction(function () use ($centro, $data) {
            $centro->descripcion = $data['descripcion'];
            $centro->ubicacion = $data['ubicacion'] ?? null;
            $centro->distrito = $data['distrito'] ?? null;
            $centro->editor_id = Auth::id();
            $centro->save();
        });

        return redirect()->route('centros.index')->with('success', 'Centro de votación actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un centro de votación.
     */
    public function destroy(Centro $centro)
    {
        DB::transaction(function () use ($centro) {
            $centro->delete();
        });

        return redirect()->route('centros.index')->with('success', 'Centro de votación eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar un centro de votación.
     */
    private function validar(Request $request, ?Centro $centro = null)
    {
        return $request->validate([
            'descripcion' => [
                'required', 'string', 'max:255',
                Rule::unique('centros', 'descripcion')
                    ->whereNull('deleted_at')
                    ->ignore($centro?->centro_id, 'centro_id'),
            ],
            'ubicacion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:255',
        ], [
            'descripcion.unique' => 'Ya existe un centro de votación con esa descripción.',
        ], [
            'descripcion' => 'descripción',
            'ubicacion' => 'ubicación',
            'distrito' => 'distrito',
        ]);
    }
}