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
        $partidos = Partido::orderBy('orden')->orderBy('nombre')
            ->get();

        return view('partidos.index', compact('partidos'));
    }

    /**
     * Formulario para registrar un nuevo partido.
     */
    public function create()
    {
        $partido = new Partido();
        $partido->orden = (int) (Partido::max('orden') ?? 0) + 1;

        return view('partidos.create', compact('partido'));
    }

    /**
     * Almacena un nuevo partido.
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $partido = null;

        DB::transaction(function () use ($data, &$partido) {
            $partido = new Partido();
            $partido->nombre = $data['nombre'];
            $partido->orden = $data['orden'];
            $partido->autor_id = Auth::id();
            $partido->editor_id = Auth::id();
            $partido->save();
        });

        // El logo se almacena una vez confirmada la transacción.
        $this->guardarLogo($request, $partido);

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
        $data = $this->validar($request, $partido);

        DB::transaction(function () use ($partido, $data) {
            $partido->nombre = $data['nombre'];
            $partido->orden = $data['orden'];
            $partido->editor_id = Auth::id();
            $partido->save();
        });

        // Reemplaza el logo solo si se envió uno nuevo.
        $this->guardarLogo($request, $partido);

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
    private function validar(Request $request, ?Partido $partido = null)
    {
        $ignorar = $partido?->partido_id ? ','.$partido->partido_id.',partido_id' : '';

        return $request->validate([
            'nombre' => 'required|string|max:255',
            'orden' => 'required|integer|min:1|unique:partidos,orden'.$ignorar,
            'logo' => 'nullable|image|max:2048',
        ], [
            'orden.unique' => 'El orden ingresado ya está en uso por otro partido.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.max' => 'El logo no puede superar los 2 MB.',
        ], [
            'nombre' => 'nombre del partido',
            'orden' => 'orden',
            'logo' => 'logo',
        ]);
    }

    /**
     * Guarda el logo enviado en public/img/partidos y actualiza la ruta
     * almacenada en el partido. Si no se envía archivo, conserva el actual.
     */
    private function guardarLogo(Request $request, Partido $partido): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        $archivo = $request->file('logo');
        $directorio = public_path('img/partidos');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // Elimina el logo anterior para no dejar archivos huérfanos.
        if ($partido->logo && is_file(public_path($partido->logo))) {
            unlink(public_path($partido->logo));
        }

        $nombre = 'partido_'.$partido->partido_id.'_'.time().'.'.$archivo->extension();
        $archivo->move($directorio, $nombre);

        $partido->logo = 'img/partidos/'.$nombre;
        $partido->save();
    }
}
