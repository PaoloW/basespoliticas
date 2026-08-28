<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
{
    /**
     * Listado de cargos.
     */
    public function index()
    {
        $cargos = Cargo::withCount('afiliados')
            ->orderBy('descripcion')
            ->get();

        return view('cargos.index', compact('cargos'));
    }

    /**
     * Formulario para registrar un nuevo cargo.
     */
    public function create()
    {
        $cargo = new Cargo();

        return view('cargos.create', compact('cargo'));
    }

    /**
     * Almacena un nuevo cargo.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $cargo = new Cargo();
            $cargo->descripcion = $data['descripcion'];
            $cargo->autor_id = Auth::id();
            $cargo->editor_id = Auth::id();
            $cargo->save();
        });

        return redirect()->route('cargos.index')->with('success', 'Cargo registrado correctamente.');
    }

    /**
     * Formulario para editar un cargo existente.
     */
    public function edit(Cargo $cargo)
    {
        return view('cargos.edit', compact('cargo'));
    }

    /**
     * Actualiza los datos de un cargo.
     */
    public function update(Request $request, Cargo $cargo)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($cargo, $data) {
            $cargo->descripcion = $data['descripcion'];
            $cargo->editor_id = Auth::id();
            $cargo->save();
        });

        return redirect()->route('cargos.index')->with('success', 'Cargo actualizado correctamente.');
    }

    /**
     * Elimina (soft delete) un cargo.
     */
    public function destroy(Cargo $cargo)
    {
        DB::transaction(function () use ($cargo) {
            $cargo->delete();
        });

        return redirect()->route('cargos.index')->with('success', 'Cargo eliminado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar un cargo.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'descripcion' => 'required|string|max:255',
        ], [], [
            'descripcion' => 'descripción',
        ]);
    }
}