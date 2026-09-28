<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Partido;
use App\Models\Persona;
use App\Models\Personero;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class VotoController extends Controller
{
    /**
     * Reporte de conteos: mesas con votos registrados y su total.
     */
    public function index()
    {
        $personeroActual = $this->personeroAutenticado();

        $consulta = Mesa::with(['centro', 'personero.persona'])
            ->withCount('votos')
            ->withSum('votos as total_votos', 'votos')
            ->withMax('votos as ultimo_conteo', 'updated_at')
            // Referencia al primer conteo de la mesa: permite abrir la edición con la mesa bloqueada.
            ->withMin('votos as voto_id', 'voto_id')
            ->whereHas('votos');

        // Un personero solo ve su propia mesa; el administrador ve todas.
        if ($personeroActual && ! Auth::user()?->esAdmin()) {
            $consulta->where('mesa_id', $personeroActual->mesa_id);
        }

        $mesas = $consulta->get()
            ->sortByDesc(fn (Mesa $mesa) => $mesa->total_votos)
            ->values();

        $totalVotos = (int) $mesas->sum('total_votos');
        $totalPartidos = (int) Voto::distinct()->count('partido_id');

        return view('votos.index', compact('mesas', 'totalVotos', 'totalPartidos'));
    }

    /**
     * Formulario directo de registro del conteo de votos.
     *
     * Se elige la mesa de todas las registradas; si tiene personero y votos
     * se precargan, si no se permite registrar el personero (por DNI) y el conteo.
     */
    public function registrar(Request $request)
    {
        $voto = new Voto();
        $partidos = $this->partidosOrdenados();
        $personeroActual = $this->personeroAutenticado();
        $esAdmin = (bool) Auth::user()?->esAdmin();

        // Mesa del formulario: la del personero activo o la recibida por parámetro.
        $mesaSel = $personeroActual?->mesa()->with(['centro', 'personero.persona'])->first()
            ?? ($request->filled('mesa_id') ? Mesa::with(['centro', 'personero.persona'])->find($request->input('mesa_id')) : null);

        $personeroSel = $mesaSel?->personero;
        $mesas = $this->mesasOrdenadas();
        $conteos = $this->conteosDe($mesaSel?->mesa_id);
        // Persona elegida para la mesa cuando aún no tiene personero: retorno del
        // alta de una persona nueva (?persona_id=) o de una búsqueda por ?dni=.
        $personaElegida = $personeroSel ? null : $this->personaDeRetorno($request);
        $personaSeleccionada = $personeroSel?->persona ?? $personeroActual?->persona ?? $personaElegida;

        return view('votos.registrar', compact('voto', 'partidos', 'mesas', 'mesaSel', 'personeroActual', 'personeroSel', 'conteos', 'personaSeleccionada', 'personaElegida', 'esAdmin'));
    }

    /**
     * Guarda el conteo por cada partido de la mesa.
     */
    public function guardar(Request $request)
    {
        $personeroActual = $this->personeroAutenticado();

        // Un personero solo registra el conteo de su mesa.
        if ($personeroActual) {
            $request->merge(['mesa_id' => $personeroActual->mesa_id]);
        }

        $data = $this->validar($request);
        $conteos = $this->normalizarConteos($request);

        DB::transaction(function () use ($data, $conteos, $request) {
            $this->asegurarPersonero((int) $data['mesa_id'], $request->input('persona_id'));
            foreach ($conteos as $partidoId => $votos) {
                $this->guardarConteo((int) $data['mesa_id'], $partidoId, $votos, Auth::id());
            }
        });

        return redirect()->route('votos.index')->with('success', 'Conteo de votos registrado correctamente.');
    }

    /**
     * Formulario para actualizar el conteo completo de una mesa.
     */
    public function edit(Voto $voto)
    {
        $mesa = $voto->mesa()->with(['centro', 'personero.persona'])->first();
        $partidos = $this->partidosOrdenados();
        $conteos = $this->conteosDe($voto->mesa_id);
        $personero = $mesa?->personero;
        $personaSeleccionada = $personero?->persona;
        $mesas = collect();
        $mesaSel = $mesa;
        $personeroSel = $personero;
        $esAdmin = (bool) Auth::user()?->esAdmin();

        return view('votos.edit', compact('voto', 'mesa', 'mesaSel', 'personero', 'personeroSel', 'partidos', 'conteos', 'personaSeleccionada', 'mesas', 'esAdmin'));
    }

    /**
     * Actualiza el conteo por cada partido de la mesa elegida en el formulario.
     */
    public function update(Request $request, Voto $voto)
    {
        // En edición la mesa está bloqueada: siempre se usa la mesa del registro.
        $request->merge(['mesa_id' => $voto->mesa_id]);
        $data = $this->validar($request);
        $conteos = $this->normalizarConteos($request);

        DB::transaction(function () use ($data, $conteos, $request) {
            // Solo se añade personero si la mesa aún no tiene; si ya existe se bloquea.
            $this->asegurarPersonero((int) $data['mesa_id'], $request->input('persona_id'));
            foreach ($conteos as $partidoId => $votos) {
                $this->guardarConteo((int) $data['mesa_id'], $partidoId, $votos, Auth::id());
            }
        });

        return redirect()->route('votos.index')->with('success', 'Conteo de votos actualizado correctamente.');
    }

    /**
     * Detalle de una mesa con su personero y conteos (AJAX): se busca por
     * mesa_id o por número de mesa.
     */
    public function buscarMesa(Request $request)
    {
        $mesa = null;

        if ($request->filled('mesa_id')) {
            $mesa = Mesa::with(['centro', 'personero.persona'])->find($request->input('mesa_id'));
        } elseif ($request->filled('personero_id')) {
            // Compatibilidad: antes se buscaba por personero.
            $personero = Personero::with(['mesa.centro', 'persona'])->find($request->input('personero_id'));
            $mesa = $personero?->mesa()->with(['centro', 'personero.persona'])->first();
        } else {
            $mesaNumero = preg_replace('/\D/', '', (string) $request->query('mesa', $request->query('dni', '')));

            if ($mesaNumero === '') {
                return response()->json(['mensaje' => 'Ingrese un número de mesa para buscar.'], 422);
            }

            $mesa = Mesa::with(['centro', 'personero.persona'])
                ->where('descripcion', $mesaNumero)
                ->first();
        }

        if (! $mesa) {
            return response()->json(['mensaje' => 'No se encontró la mesa buscada.'], 404);
        }

        return response()->json($this->detalleMesa($mesa));
    }

    /**
     * Busca una persona por DNI para registrarla como personero de la mesa (AJAX).
     * La afiliación es opcional: solo se informa si la persona la tiene.
     */
    public function buscarPersona(Request $request)
    {
        $dni = $this->normalizarDni($request->query('dni'));

        if ($dni === '') {
            return response()->json(['mensaje' => 'Ingrese un DNI para buscar la persona.'], 422);
        }

        $persona = $this->personaPorDni($dni);

        if (! $persona) {
            return response()->json(['mensaje' => 'No se encontró ninguna persona con el DNI ingresado.'], 404);
        }

        if ($persona->esPersonero()) {
            $personero = Personero::with('mesa')->where('persona_id', $persona->persona_id)->first();
            $mesa = $personero?->mesa?->etiqueta() ?? 'otra mesa';

            return response()->json([
                'mensaje' => 'La persona '.$persona->apellidoNombre().' (DNI: '.$persona->dni.') ya está registrada como personero de la '.$mesa.'.',
            ], 409);
        }

        return response()->json($this->datosPersona($persona));
    }

    /**
     * Mesas registradas para el modal (paginado en el servidor).
     */
    public function personerosModal()
    {
        $query = Mesa::query()
            ->leftJoin('centros', 'centros.centro_id', '=', 'mesas.centro_id')
            ->leftJoin('personeros', 'personeros.mesa_id', '=', 'mesas.mesa_id')
            ->leftJoin('personas', 'personas.persona_id', '=', 'personeros.persona_id')
            ->select(['mesas.mesa_id', 'mesas.descripcion', 'personas.dni']);

        return DataTables::eloquent($query)
            ->addColumn('persona', function (Mesa $mesa) {
                $mesa->loadMissing(['personero.persona']);

                return $mesa->personero?->persona?->apellidoNombre() ?? '— Sin personero —';
            })
            ->addColumn('mesa', function (Mesa $mesa) {
                return $mesa->etiqueta();
            })
            ->addColumn('centro', function (Mesa $mesa) {
                $mesa->loadMissing(['centro']);

                return $mesa->centro?->descripcion ?? '—';
            })
            ->toJson();
    }

    /**
     * Personas sin personero vigente para el modal de votos (paginado en el
     * servidor). Muestra la afiliación cuando la tienen; es opcional.
     */
    public function personasModal()
    {
        $query = Persona::query()
            ->with(['afiliados.base', 'afiliados.cargo'])
            ->whereDoesntHave('personeros')
            ->select(['persona_id', 'dni', 'nombres', 'primer_apellido', 'segundo_apellido', 'telefono']);

        return DataTables::eloquent($query)
            ->addColumn('persona', fn (Persona $persona) => $persona->apellidoNombre())
            ->addColumn('afiliacion', fn (Persona $persona) => $persona->descripcionAfiliacion())
            ->filterColumn('persona', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('nombres', 'like', "%{$keyword}%")
                        ->orWhere('primer_apellido', 'like', "%{$keyword}%")
                        ->orWhere('segundo_apellido', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('afiliacion', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('afiliados.base', fn ($base) => $base->where('descripcion', 'like', "%{$keyword}%"))
                        ->orWhereHas('afiliados.cargo', fn ($cargo) => $cargo->where('descripcion', 'like', "%{$keyword}%"));
                });
            })
            ->toJson();
    }

    /**
     * Devuelve el conteo registrado de una mesa (JSON) para precargar el formulario.
     */
    public function conteo(Mesa $mesa)
    {
        $mesa->loadMissing(['personero.persona', 'centro']);

        return response()->json($this->detalleMesa($mesa));
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
     * Detalle de la mesa para el formulario de conteo.
     */
    private function detalleMesa(Mesa $mesa): array
    {
        $personero = $mesa->personero;

        return [
            'mesa_id' => $mesa->mesa_id,
            'mesa' => $mesa->etiqueta(),
            'mesa_numero' => $mesa->descripcion,
            'centro' => $mesa->centro?->descripcion,
            'personero_id' => $personero?->personero_id,
            'persona_id' => $personero?->persona_id,
            'dni' => $personero?->persona?->dni,
            'nombre_completo' => $personero?->persona?->apellidoNombre(),
            'afiliacion' => $personero?->descripcionAfiliacion(),
            'tiene_personero' => (bool) $personero,
            'conteos' => $this->conteosDe($mesa->mesa_id),
        ];
    }

    /**
     * Persona a mostrar como personero de la mesa: por ?persona_id= o por ?dni=
     * (retorno del alta de una persona nueva o de una búsqueda fallida).
     */
    private function personaDeRetorno(Request $request): ?Persona
    {
        $personaId = old('persona_id', $request->query('persona_id'));

        if ($personaId) {
            $persona = Persona::with(['afiliados.base', 'afiliados.cargo'])->find($personaId);

            if ($persona) {
                return $persona;
            }
        }

        $dni = $this->normalizarDni(old('dni_personero', $request->query('dni')));

        return $dni === '' ? null : $this->personaPorDni($dni);
    }

    /**
     * Datos de la persona para el buscador por DNI (la afiliación es opcional).
     */
    private function datosPersona(Persona $persona): array
    {
        return [
            'persona_id' => $persona->persona_id,
            'dni' => $persona->dni,
            'nombre_completo' => $persona->apellidoNombre(),
            'telefono' => $persona->telefono,
            'afiliacion' => $persona->descripcionAfiliacion(),
        ];
    }

    /**
     * DNI normalizado (solo dígitos).
     */
    private function normalizarDni(mixed $dni): string
    {
        return preg_replace('/\D/', '', (string) $dni);
    }

    /**
     * Persona por DNI, tolerando puntos, guiones y espacios en el valor guardado.
     */
    private function personaPorDni(string $dni): ?Persona
    {
        return Persona::with(['afiliados.base', 'afiliados.cargo'])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(TRIM(dni), '.', ''), '-', ''), ' ', '') = ?",
                [$dni]
            )->first();
    }

    /**
     * Partidos ordenados por orden y nombre, para la tabla del formulario.
     */
    private function partidosOrdenados()
    {
        return Partido::orderBy('orden')->orderBy('nombre')->get();
    }

    /**
     * Mesas ordenadas por número, para el selector del formulario.
     */
    private function mesasOrdenadas()
    {
        return Mesa::with(['centro', 'personero.persona'])->orderBy('descripcion')->get();
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
     * Conteos registrados de una mesa, indexados por partido.
     */
    private function conteosDe(?int $mesaId): array
    {
        if (! $mesaId) {
            return [];
        }

        return Voto::where('mesa_id', $mesaId)
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
     * Reglas de validación de la mesa, de la persona y de los votos por partido.
     */
    private function validar(Request $request)
    {
        return $request->validate([
            'mesa_id' => 'required|integer|exists:mesas,mesa_id',
            'persona_id' => 'nullable|integer|exists:personas,persona_id',
            'votos' => 'required|array',
            'votos.*' => 'nullable|integer|min:0',
        ], [], [
            'mesa_id' => 'mesa',
            'persona_id' => 'personero',
            'votos' => 'votos',
        ]);
    }

    /**
     * Registra el personero de la mesa si aún no tiene, usando la persona elegida.
     * La afiliación no es un requisito: solo se informa si la persona la tiene.
     */
    private function asegurarPersonero(int $mesaId, mixed $personaId): ?Personero
    {
        $mesa = Mesa::with('personero')->findOrFail($mesaId);

        if ($mesa->personero || ! $personaId) {
            return $mesa->personero;
        }

        $persona = Persona::findOrFail((int) $personaId);

        $personero = new Personero();
        $personero->persona_id = $persona->persona_id;
        $personero->mesa_id = $mesa->mesa_id;
        $personero->autor_id = Auth::id();
        $personero->editor_id = Auth::id();
        $personero->save();

        return $personero;
    }

    /**
     * Crea o actualiza el conteo de un partido para la mesa.
     */
    private function guardarConteo(int $mesaId, int $partidoId, int $votos, ?int $usuarioId): void
    {
        $registro = Voto::withTrashed()
            ->where('mesa_id', $mesaId)
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
        $registro->mesa_id = $mesaId;
        $registro->partido_id = $partidoId;
        $registro->votos = $votos;
        $registro->autor_id = $usuarioId;
        $registro->editor_id = $usuarioId;
        $registro->save();
    }
}
