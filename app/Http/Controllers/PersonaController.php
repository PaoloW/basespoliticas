<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller
{
    /**
     * Listado de personas del sistema.
     */
    public function index()
    {
        $personas = Persona::with('usuario')
            ->withCount('afiliados')
            ->orderBy('persona_id', 'desc')
            ->get();

        return view('personas.index', compact('personas'));
    }

    /**
     * Formulario para registrar una nueva persona.
     */
    public function create()
    {
        $persona = new Persona();
        // La clave primaria de persona no es autoincremental; se asigna el siguiente id libre.
        $persona->persona_id = (Persona::withTrashed()->max('persona_id') ?? 0) + 1;

        return view('personas.create', compact('persona'));
    }

    /**
     * Almacena una nueva persona.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $persona = new Persona();
            $persona->persona_id = (Persona::withTrashed()->max('persona_id') ?? 0) + 1;
            $persona->dni = $data['dni'];
            $persona->nombres = $data['nombres'];
            $persona->primer_apellido = $data['primer_apellido'];
            $persona->segundo_apellido = $data['segundo_apellido'] ?? null;
            $persona->codigo_mesa = $data['codigo_mesa'] ?? null;
            $persona->fecha_nacimiento = $data['fecha_nacimiento'] ?? null;
            $persona->telefono = $data['telefono'] ?? null;
            $persona->autor_id = Auth::id();
            $persona->editor_id = Auth::id();
            $persona->save();
        });

        return redirect()->route('personas.index')->with('success', 'Persona registrada correctamente.');
    }

    /**
     * Formulario para editar una persona existente.
     */
    public function edit(Persona $persona)
    {
        return view('personas.edit', compact('persona'));
    }

    /**
     * Actualiza los datos de una persona.
     */
    public function update(Request $request, Persona $persona)
    {
        $data = $this->validar($request, $persona);

        DB::transaction(function () use ($persona, $data) {
            $persona->dni = $data['dni'];
            $persona->nombres = $data['nombres'];
            $persona->primer_apellido = $data['primer_apellido'];
            $persona->segundo_apellido = $data['segundo_apellido'] ?? null;
            $persona->codigo_mesa = $data['codigo_mesa'] ?? null;
            $persona->fecha_nacimiento = $data['fecha_nacimiento'] ?? null;
            $persona->telefono = $data['telefono'] ?? null;
            $persona->editor_id = Auth::id();
            $persona->save();
        });

        return redirect()->route('personas.index')->with('success', 'Persona actualizada correctamente.');
    }

    /**
     * Elimina (soft delete) una persona.
     */
    public function destroy(Persona $persona)
    {
        DB::transaction(function () use ($persona) {
            $persona->delete();
        });

        return redirect()->route('personas.index')->with('success', 'Persona eliminada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica interna
    |--------------------------------------------------------------------------
    */

    /**
     * Reglas de validación al crear o actualizar una persona.
     */
    private function validar(Request $request, ?Persona $persona = null)
    {
        $dni = 'required|string|max:255|unique:personas,dni';
        if ($persona) {
            $dni .= ','.$persona->persona_id.',persona_id';
        }

        return $request->validate([
            'dni' => $dni,
            'nombres' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'codigo_mesa' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:255',
        ], [], [
            'dni' => 'DNI',
            'nombres' => 'nombres',
            'primer_apellido' => 'primer apellido',
            'segundo_apellido' => 'segundo apellido',
            'codigo_mesa' => 'código de mesa',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'telefono' => 'teléfono',
        ]);
    }
}