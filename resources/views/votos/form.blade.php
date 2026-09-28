@if ( $errors->any() )
    <div class="alert alert-warning alert-dismissible fade show">
        <strong><i class="fas fa-exclamation-triangle me-1"></i>Verifique los siguientes campos:</strong>
        <ul class="mb-0 mt-1">
            @foreach ( $errors->all() as $error )
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php
    $esEdicion = $esEdicion ?? false;
    $esPersonero = ! empty( $personeroActual );
    $personeroFijo = $esPersonero ? $personeroActual : ( $personero ?? null );
    $conteoActual = $conteos ?? [];
    $personeroSelect = $personeroSel ?? $personeroFijo;
    // Usuario sin registro de personero ni opción de elegir uno.
    $sinPersonero = ! $esEdicion && ! $esPersonero && $personeros->isEmpty();
@endphp

@if ( $sinPersonero )
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i>Su usuario no está asociado a ningún personero.
        Solicite al administrador que lo registre como personero para registrar el conteo de votos.
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-vote-yea me-2"></i><strong>Datos del conteo</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label"><strong>Personero</strong></label>
                        @if ( $esPersonero || $esEdicion )
                            <input type="text" class="form-control" readonly
                                   value="{{ $personeroFijo?->persona?->apellidoNombre() }} (DNI: {{ $personeroFijo?->persona?->dni }})">
                            <input type="hidden" name="personero_id" id="personero_id" value="{{ $personeroFijo?->personero_id }}">
                        @else
                            <select class="form-select" name="personero_id" id="personero_id" required>
                                <option value="">Seleccione un personero</option>
                                @forelse ( $personeros as $personeroItem )
                                    <option value="{{ $personeroItem->personero_id }}"
                                            data-mesa="{{ $personeroItem->mesa?->etiqueta() }}"
                                            @if ( old( 'personero_id', $personeroSelect?->personero_id ) == $personeroItem->personero_id ) selected @endif>
                                        {{ $personeroItem->persona?->apellidoNombre() }} — DNI {{ $personeroItem->persona?->dni }} — {{ $personeroItem->mesa?->etiqueta() }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay personeros registrados</option>
                                @endforelse
                            </select>
                            <div class="form-text">
                                Registre personeros en el módulo <a href="{{ route('personeros.index') }}">Personeros</a>.
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label"><strong>Mesa de votación</strong></label>
                        <input type="text" class="form-control" readonly id="mesa_personero"
                               value="{{ $personeroSelect?->mesa?->etiqueta() }}">
                        <div class="form-text">La mesa se toma del personero seleccionado.</div>
                    </div>
                </div>
            </div>
        </div>

<div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list-ol me-2"></i><strong>Conteo de votos por partido</strong>
                <p class="small text-muted my-1"><em>Registre la cantidad de votos de cada partido</em></p>
            </div>
            <div class="card-body">
                @if ( $partidos->isEmpty() )
                    <div class="alert alert-info mb-0" role="alert">
                        <i class="fas fa-info-circle me-1"></i>No hay partidos registrados. Regístrelos en el módulo
                        <a href="{{ route('partidos.index') }}" class="alert-link">Partidos</a>.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">Logo</th>
                                    <th style="width: 65%">Partido</th>
                                    <th class="text-center">Votos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ( $partidos as $partido )
                                <tr>
                                    <td class="text-center">
                                        @if (!empty($partido->logo))
                                            <img src="{{ asset($partido->logo) }}" alt="Logo" style="height: 38px; width: auto; max-width: 60px; object-fit: contain;">
                                        @else
                                            <span class="placeholder-logo" title="Sin logo"><i class="fas fa-flag"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Partido bloqueado: solo lectura --}}
                                        <input type="text" class="form-control" value="{{ $partido->nombre }}" readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control text-center" min="0" step="1" required
                                               name="votos[{{ $partido->partido_id }}]"
                                               value="{{ old( 'votos.'.$partido->partido_id, $conteoActual[$partido->partido_id] ?? 0 ) }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
<style>.placeholder-logo{display:inline-flex;align-items:center;justify-content:center;height:38px;width:38px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:.375rem;color:#adb5bd}</style>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( $esEdicion )
                    <p class="small text-muted mb-1">Registrado: {{ $voto->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $voto->updated_at?->format('d/m/Y H:i') }}</p>
                @else
                    <p class="small text-muted mb-3">
                        Registre la cantidad de votos de cada partido. Se guarda un conteo por personero y partido.
                    </p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" @if ( $partidos->isEmpty() || $sinPersonero ) disabled @endif>
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('votos.index') }}">
                        <i class="fas fa-arrow-left me-2"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@csrf

@section('footer')
    <script>
        $(document).ready(function () {
            const urlConteo = "{{ url('votos/personero') }}";
            const $personero = $('#personero_id');
            const $mesa = $('#mesa_personero');

            // Vuelca los conteos recibidos en los inputs de votos.
            const pintarConteos = function (conteos) {
                $('input[name^="votos["]').each(function () {
                    const nombre = $(this).attr('name') || '';
                    const partidoId = nombre.replace(/[^0-9]/g, '');
                    $(this).val((conteos && conteos[partidoId] !== undefined) ? conteos[partidoId] : 0);
                });
            };

            // Al elegir un personero (solo administrador) se precarga su conteo y su mesa.
            if ($personero.is('select')) {
                $personero.on('change', function () {
                    const personeroId = $(this).val();
                    const $opcion = $(this).find('option:selected');
                    $mesa.val($opcion.data('mesa') || '');

                    if (!personeroId) {
                        pintarConteos({});
                        return;
                    }

                    $.getJSON(urlConteo + '/' + personeroId + '/conteo')
                        .done(function (data) {
                            pintarConteos(data.conteos || {});
                        });
                });
            }

            $('form').on('submit', function () {
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection