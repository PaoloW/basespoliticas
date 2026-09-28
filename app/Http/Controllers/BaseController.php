<?php

namespace App\Http\Controllers;

use App\Models\Base;
use App\Models\Partido;
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
        $bases = Base::with('partido')
            ->withCount('afiliados')
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
        $partidos = $this->partidosOrdenados();

        return view('bases.create', compact('base', 'partidos'));
    }

    /**
     * Almacena una nueva base.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $base = new Base();
            $base->partido_id = $data['partido_id'] ?? null;
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
        $partidos = $this->partidosOrdenados();

        return view('bases.edit', compact('base', 'partidos'));
    }

    /**
     * Actualiza los datos de una base.
     */
    public function update(Request $request, Base $base)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($base, $data) {
            $base->partido_id = $data['partido_id'] ?? null;
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
            'partido_id' => 'nullable|integer|exists:partidos,partido_id',
            'descripcion' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
        ], [], [
            'partido_id' => 'partido',
            'descripcion' => 'descripción',
            'ubicacion' => 'ubicación',
        ]);
    }

    /**
     * Partidos ordenados por nombre, para el select del formulario.
     */
    private function partidosOrdenados()
    {
        return Partido::orderBy('nombre')->get();
    }
}