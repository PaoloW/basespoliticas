<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Personero;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VotoController extends Controller
{
    /**
     * Reporte de conteos: personeros que registraron votos y su total.
     */
    public function index()
    {
        $personeroActual = $this->personeroAutenticado();

        $consulta = Personero::with(['persona', 'mesa.centro'])
            ->withCount('votos')
            ->withSum('votos as total_votos', 'votos')
            ->withMax('votos as ultimo_conteo', 'updated_at')
            ->whereHas('votos');

        // Un personero solo ve su propio conteo; el administrador ve todos.
        if ($personeroActual && ! Auth::user()?->esAdmin()) {
            $consulta->where('personero_id', $personeroActual->personero_id);
        }

        $personeros = $consulta->get()
            ->sortByDesc(fn (Personero $personero) => $personero->total_votos)
            ->values();

        $totalVotos = (int) $personeros->sum('total_votos');
        $totalPartidos = (int) Voto::distinct()->count('partido_id');

        return view('votos.index', compact('personeros', 'totalVotos', 'totalPartidos'));
    }

    /**
     * Formulario directo de registro del conteo de votos.
     *
     * El personero se obtiene del usuario activo (a través de su persona_id);
     * el administrador puede elegir otro personero desde el selector.
     */
    public function registrar(Request $request)
    {
        $voto = new Voto();
        $partidos = $this->partidosOrdenados();
        $personeroActual = $this->personeroAutenticado();
        $esAdmin = (bool) Auth::user()?->esAdmin();

        // Personero del formulario: el del usuario activo o el recibido por parámetro.
        $personeroSel = $personeroActual
            ?? ($request->filled('personero_id') ? Personero::with('mesa')->find($request->input('personero_id')) : null);

        // Solo el administrador puede registrar el conteo de otro personero.
        $personeros = ($personeroActual || ! $esAdmin) ? collect() : $this->personerosOrdenados();
        $conteos = $this->conteosDe($personeroSel?->personero_id);

        return view('votos.registrar', compact('voto', 'partidos', 'personeros', 'personeroActual', 'personeroSel', 'conteos'));
    }

    /**
     * Guarda el conteo por cada partido del personero.
     */
    public function guardar(Request $request)
    {
        $personeroActual = $this->personeroAutenticado();

        // Un personero solo registra su propio conteo.
        if ($personeroActual) {
            $request->merge(['personero_id' => $personeroActual->personero_id]);
        }

        $data = $this->validar($request);
        $conteos = $this->normalizarConteos($request);

        DB::transaction(function () use ($data, $conteos) {
            foreach ($conteos as $partidoId => $votos) {
                $this->guardarConteo((int) $data['personero_id'], $partidoId, $votos, Auth::id());
            }
        });

        return redirect()->route('votos.index')->with('success', 'Conteo de votos registrado correctamente.');
    }

    /**
     * Formulario para actualizar el conteo completo de un personero.
     */
    public function edit(Voto $voto)
    {
        $personero = $voto->personero;
        $partidos = $this->partidosOrdenados();
        $conteos = $this->conteosDe($voto->personero_id);

        return view('votos.edit', compact('voto', 'personero', 'partidos', 'conteos'));
    }

    /**
     * Actualiza el conteo por cada partido del personero del registro.
     */
    public function update(Request $request, Voto $voto)
    {
        $request->merge(['personero_id' => $voto->personero_id]);

        $data = $this->validar($request);
        $conteos = $this->normalizarConteos($request);

        DB::transaction(function () use ($data, $conteos) {
            foreach ($conteos as $partidoId => $votos) {
                $this->guardarConteo((int) $data['personero_id'], $partidoId, $votos, Auth::id());
            }
        });

        return redirect()->route('votos.index')->with('success', 'Conteo de votos actualizado correctamente.');
    }

    /**
     * Devuelve el conteo registrado de un personero (JSON) para precargar el formulario.
     */
    public function conteo(Personero $personero)
    {
        return response()->json([
            'personero_id' => $personero->personero_id,
            'conteos' => $this->conteosDe($personero->personero_id),
        ]);
    }

    /**
     * Elimina (soft delete) un registro de votos.
     */
    public function destroy(Voto $voto)
    {
        DB::transaction(function () use ($voto) {
            $voto->editor_id = Auth::id();
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
     * Partidos ordenados por nombre, para la tabla del formulario.
     */
    private function partidosOrdenados()
    {
        return Partido::orderBy('nombre')->get();
    }

    /**
     * Personeros ordenados por apellidos y nombres, para el select del formulario.
     */
    private function personerosOrdenados()
    {
        return Personero::with('persona')
            ->get()
            ->sortBy(fn (Personero $personero) => $personero->persona?->apellidoNombre())
            ->values();
    }

    /**
     * Personero vinculado a la persona del usuario autenticado (o null).
     */
    private function personeroAutenticado(): ?Personero
    {
        $personaId = Auth::user()?->persona_id;

        if (! $personaId) {
            return null;
        }

        return Personero::with('mesa')->where('persona_id', $personaId)->first();
    }

    /**
     * Conteos registrados de un personero, indexados por partido.
     */
    private function conteosDe(?int $personeroId): array
    {
        if (! $personeroId) {
            return [];
        }

        return Voto::where('personero_id', $personeroId)
            ->pluck('votos', 'partido_id')
            ->toArray();
    }

    /**
     * Normaliza los votos recibidos (votos[partido_id]) a un arreglo partido => votos.
     */
    private function normalizarConteos(Request $request): array
    {
        $conteos = [];

        foreach ((array) $request->input('votos', []) as $partidoId => $votos) {
            if (! is_numeric($partidoId) || ! is_numeric($votos)) {
                continue;
            }

            $conteos[(int) $partidoId] = max(0, (int) $votos);
        }

        return $conteos;
    }

    /**
     * Reglas de validación del personero y de los votos por partido.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'personero_id' => 'required|integer|exists:personeros,personero_id',
            'votos' => 'required|array',
            'votos.*' => 'nullable|integer|min:0',
        ], [], [
            'personero_id' => 'personero',
            'votos' => 'votos',
        ]);
    }

    /**
     * Crea o actualiza el conteo de un partido para el personero.
     */
    private function guardarConteo(int $personeroId, int $partidoId, int $votos, ?int $usuarioId): void
    {
        $registro = Voto::withTrashed()
            ->where('personero_id', $personeroId)
            ->where('partido_id', $partidoId)
            ->first();

        if ($registro) {
            if ($registro->trashed()) {
                $registro->restore();
            }
            $registro->votos = $votos;
            $registro->editor_id = $usuarioId;
            $registro->save();

            return;
        }

        $registro = new Voto();
        $registro->personero_id = $personeroId;
        $registro->partido_id = $partidoId;
        $registro->votos = $votos;
        $registro->autor_id = $usuarioId;
        $registro->editor_id = $usuarioId;
        $registro->save();
    }
}
