<?php

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaseController extends Controller
{
    /**
     * Listado de bases.
     */
    public function index()
    {
        $bases = Base::withCount('afiliados')
            ->orderBy('descripcion')
            ->get();

        return view('bases.index', compact('bases'));
    }

    /**
     * Formulario para registrar una nueva base.
     */
    public function create()
    {
        $base = new Base();

        return view('bases.create', compact('base'));
    }

    /**
     * Almacena una nueva base.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $base = new Base();
            $base->descripcion = $data['descripcion'];
            $base->ubicacion = $data['ubicacion'] ?? null;
            $base->autor_id = Auth::id();
            $base->editor_id = Auth::id();
            $base->save();
        });

        return redirect()->route('bases.index')->with('success', 'Base registrada correctamente.');
    }

    /**
     * Formulario para editar una base existente.
     */
    public function edit(Base $base)
    {
        return view('bases.edit', compact('base'));
    }

    /**
     * Actualiza los datos de una base.
     */
    public function update(Request $request, Base $base)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($base, $data) {
            $base->descripcion = $data['descripcion'];
            $base->ubicacion = $data['ubicacion'] ?? null;
            $base->editor_id = Auth::id();
            $base->save();
        });

        return redirect()->route('bases.index')->with('success', 'Base actualizada correctamente.');
    }

    /**
     * Elimina (soft delete) una base.
     */
    public function destroy(Base $base)
    {
        DB::transaction(function () use ($base) {
            $base->delete();
        });

        return redirect()->route('bases.index')->with('success', 'Base eliminada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar una base.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'descripcion' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
        ], [], [
            'descripcion' => 'descripción',
            'ubicacion' => 'ubicación',
        ]);
    }
}