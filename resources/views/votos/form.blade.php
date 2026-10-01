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
    $esAdmin = ! empty( $esAdmin );
    $bloquearMesa = $esEdicion || $esPersonero;
    $mesaFija = $esPersonero ? $personeroActual->mesa : ( $mesa ?? null );
    $mesaSelect = $mesaSel ?? $mesaFija;
    $conteoActual = $conteos ?? [];
    $personeroSelect = $personeroSel ?? $mesaSelect?->personero ?? $mesaFija?->personero;
    $personaSeleccionada = $personaSeleccionada ?? $personeroSelect?->persona;
    $mesas = $mesas ?? collect();
    $personaElegida = $personaElegida ?? null;
    $tienePersonero = ! empty($personeroSelect);
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-vote-yea me-2"></i><strong>Datos del conteo</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="dni_buscar" class="form-label"><strong>Número de mesa</strong></label>
                        @if ( $bloquearMesa )
                            {{-- Edición: mesa bloqueada, no se puede editar ni elegir otra --}}
                            <input type="text" class="form-control" id="dni_buscar" readonly disabled
                                   value="{{ $mesaFija?->descripcion }}">
                        @else
                            <div class="input-group">
                                <input type="text" class="form-control" id="dni_buscar" name="dni_buscar"
                                       value="{{ old('dni_buscar', $mesaSelect?->descripcion ?? '') }}"
                                       placeholder="Ingrese el N° de mesa" spellcheck="false" autocorrect="off"
                                       autocapitalize="off" autocomplete="off" inputmode="numeric" maxlength="20">
                                <button type="button" class="btn btn-outline-primary" id="btnModalPersoneros"
                                        data-bs-toggle="tooltip" title="Buscar mesa en el listado">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="persona_nombre" class="form-label"><strong>Personero</strong></label>
                        <input type="text" class="form-control" id="persona_nombre" readonly
                               value="{{ old('persona_nombre', $personaSeleccionada?->apellidoNombre() ?? '') }}"
                               placeholder="Se completa al elegir la mesa">
                        <div class="form-text">Elija la mesa: se muestran sus votos y su personero si existen.</div>
                    </div>
                </div>
                <input type="hidden" name="mesa_id" id="mesa_id"
                       value="{{ old('mesa_id', $mesaSelect?->mesa_id ?? $mesaFija?->mesa_id) }}">
                <div id="resultadoPersonero" class="mb-3"></div>
                {{-- Personero de la mesa: selector compartido de personas (la afiliación es opcional) --}}
                <div id="bloquePersonero" style="display: none;">
                    @include('partials.selector-persona', [
                        'spOrigen' => 'votos',
                        'spUrlBuscar' => route('votos.buscarPersona'),
                        'spUrlModal' => route('votos.personasModal'),
                        'spPersona' => $personeroSelect?->persona ?? $personaElegida,
                        'spExtra' => ['mesa_id' => $mesaSelect?->mesa_id ?? $mesaFija?->mesa_id],
                        'spBloquear' => $bloquearMesa && $tienePersonero,
                        'spEtiqueta' => 'DNI del personero',
                        'spEtiquetaNombre' => 'Datos encontrados',
                        'spNombrePlaceholder' => 'Escriba los 8 dígitos del DNI para asignar el personero a la mesa',
                        'spNotaModal' => 'Solo se listan personas que aún no son personeros. La afiliación se muestra si la persona la tiene (es opcional).',
                        'spAyuda' => 'Al guardar el conteo también se registra el personero en la mesa.',
                    ])
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label"><strong>Mesa de votación</strong></label>
                        <input type="text" class="form-control" readonly id="mesa_personero"
                               value="{{ $mesaSelect?->etiqueta() ?? $mesaFija?->etiqueta() }}">
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label"><strong>Centro de votación</strong></label>
                        <input type="text" class="form-control" readonly id="centro_personero"
                               value="{{ $mesaSelect?->centro?->descripcion ?? $mesaFija?->centro?->descripcion }}">
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
                                <tr class="table-light fw-bold" id="filaTotalVotos">
                                    <td class="text-center"><i class="fas fa-calculator"></i></td>
                                    <td class="text-end">TOTAL DE VOTOS</td>
                                    <td class="text-center"><span id="totalVotos">0</span></td>
                                </tr>
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
                    <p class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-lock me-1"></i>En edición la mesa está bloqueada.
                        @if ( $tienePersonero )
                            Esta mesa ya tiene personero: solo puede modificar los votos.
                        @else
                            Solo puede añadir el personero (si falta) y modificar los votos.
                        @endif
                    </p>
                @else
                    <p class="small text-muted mb-3">
                        Registre la cantidad de votos de cada partido. Se guarda un conteo por mesa y partido.
                    </p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" @if ( $partidos->isEmpty() ) disabled @endif>
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

@if ( ! $bloquearMesa )
<div class="modal fade" id="modalPersoneros" tabindex="-1" aria-labelledby="modalPersonerosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPersonerosLabel">
                    <i class="fas fa-user-check me-2"></i>Seleccionar mesa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover w-100" id="tabla-modal-personeros">
                        <thead>
                            <tr>
                                <th>Mesa</th>
                                <th>Personero</th>
                                <th>Centro</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- El listado y alta de personas es compartido: partials/selector-persona.blade.php --}}

@csrf

@section('footer')
    <script>
        $(document).ready(function () {
            const urlBuscar = "{{ route('votos.buscarMesa') }}";
            const urlConteo = "{{ url('votos/mesa') }}";
            const $form = $('form').first();
            const $dni = $('#dni_buscar');
            const $nombre = $('#persona_nombre');
            const $mesaId = $('#mesa_id');
            // Persona que se registrará como personero de la mesa (la elige el selector compartido).
            const $personaId = $('#persona_id');
            const $mesa = $('#mesa_personero');
            const $centro = $('#centro_personero');
            const $resultado = $('#resultadoPersonero');
            const $bloquePersonero = $('#bloquePersonero');
            const esEdicion = {{ ($esEdicion ?? false) ? 'true' : 'false' }};
            // La mesa solo se puede buscar/elegir si NO está bloqueada (alta sin personero).
            const mesaBloqueada = $dni.prop('readonly') || $dni.prop('disabled') || esEdicion;
            // La mesa ya tiene personero: dato del servidor (independiente de la persona elegida).
            let mesaConPersonero = {{ $tienePersonero ? 'true' : 'false' }};

            // Mesa elegida sin personero: se muestra el registro del personero.
            if ($mesaId.val() && !mesaConPersonero) {
                $bloquePersonero.show();
            }

            // Suma todos los inputs de votos y muestra el total en la fila de pie de tabla.
            const recalcularTotal = function () {
                let total = 0;
                $('input[name^="votos["]').each(function () {
                    const valor = parseInt($(this).val(), 10);
                    total += isNaN(valor) ? 0 : valor;
                });
                $('#totalVotos').text(total.toLocaleString('es-PE'));
            };

            // Recalcula el total al salir de cada input de votos.
            $(document).on('blur change', 'input[name^="votos["]', recalcularTotal);
            recalcularTotal(); // Total inicial con los valores cargados en el servidor.

            const pintarConteos = function (conteos) {
                $('input[name^="votos["]').each(function () {
                    const nombre = $(this).attr('name') || '';
                    const partidoId = nombre.replace(/[^0-9]/g, '');
                    $(this).val((conteos && conteos[partidoId] !== undefined) ? conteos[partidoId] : 0);
                });
                recalcularTotal();
            };

            // Resetea el formulario inferior (mesa + votos) al cambiar de mesa.
            const resetearConteo = function () { $mesa.val(''); $centro.val(''); pintarConteos({}); };
            const pintarResultado = function (tipo, mensaje) {
                if (!$resultado.length) { return; }
                $resultado.html('<p class="alert alert-' + tipo + ' py-2 mb-0">' + mensaje + '</p>');
            };
            // Persona elegida con el selector: se refleja también en el campo superior.
            $(document).on('persona:seleccionada', function (event, datos) {
                if (!$mesaId.val() || mesaConPersonero) { return; }
                $nombre.val(datos.nombre_completo || '');
            });
            const fijarMesa = function (data) {
                $mesaId.val(data.mesa_id || '');
                $nombre.val(data.nombre_completo || '');
                $mesa.val(data.mesa || '');
                $centro.val(data.centro || '');
                pintarConteos(data.conteos || {});
                // Se actualiza el estado del personero según la mesa consultada.
                mesaConPersonero = !!data.tiene_personero;
                if (data.tiene_personero) {
                    $personaId.val(data.persona_id || '');
                    $bloquePersonero.hide();
                } else {
                    // La mesa no tiene personero: se limpia la persona elegida antes.
                    $personaId.val('');
                    $(document).trigger('persona:limpiar');
                    $bloquePersonero.show();
                }
            };
            const limpiarMesa = function () {
                if (mesaBloqueada) { return; }
                mesaConPersonero = false;
                $mesaId.val(''); $nombre.val('');
                $bloquePersonero.hide();
                $(document).trigger('persona:limpiar');
                resetearConteo();
            };

            if (!mesaBloqueada) {
                const normalizar = function (v) { return (v || '').replace(/\D/g, ''); };
                let temporizador = null;
                const buscar = function () {
                    const mesa = normalizar($dni.val());
                    limpiarMesa();
                    if (mesa === '') {
                        pintarResultado('info', 'Ingrese el número de mesa para ver sus datos.');
                        return;
                    }
                    $.getJSON(urlBuscar, { mesa: mesa })
                        .done(function (data) {
                            $dni.val(data.mesa_numero ?? mesa);
                            fijarMesa(data);
                            if (data.tiene_personero) {
                                pintarResultado('success', 'Mesa encontrada: <strong>' + data.mesa + '</strong> — personero <strong>' + data.nombre_completo + '</strong>. Se muestran sus votos registrados.');
                            } else {
                                pintarResultado('warning', 'Mesa encontrada: <strong>' + data.mesa + '</strong> sin personero. Busque el DNI para registrarlo y luego guarde el conteo.');
                            }
                        })
                        .fail(function (xhr) {
                            const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : 'No fue posible realizar la búsqueda.';
                            pintarResultado('danger', mensaje);
                        });
                };
                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    limpiarMesa();
                    if (normalizar($dni.val()) !== '') { temporizador = setTimeout(buscar, 300); }
                    else { pintarResultado('info', 'Ingrese el número de mesa para ver sus datos.'); }
                });
                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); buscar(); }
                });
            }

            // El personero de la mesa se busca y registra con el selector compartido
            // (partials/selector-persona.blade.php), que guarda la persona en #persona_id.
            // El evento 'persona:seleccionada' se emite cuando se elige una persona.

            // Elección de mesa desde el listado: solo si la mesa no está bloqueada.
            if (!mesaBloqueada) {
                let tablaPersoneros = null;
                $('#btnModalPersoneros').on('click', function () {
                    limpiarMesa();
                    pintarResultado('info', 'Elija una mesa del listado para ver sus votos registrados.');
                    if (!tablaPersoneros) {
                        tablaPersoneros = $('#tabla-modal-personeros').DataTable({
                            processing: true, serverSide: true,
                            language: { url: "{{ asset('datatables/spanish.json') }}" },
                            ajax: { url: "{{ route('votos.personerosModal') }}" },
                            columns: [
                                { data: 'mesa', name: 'mesas.descripcion' },
                                { data: 'persona', orderable: false, searchable: false },
                                { data: 'centro', orderable: false, searchable: false },
                                { data: null, orderable: false, searchable: false, className: 'text-end',
                                  render: function (data, type, row) {
                                      return '<button type="button" class="btn btn-sm btn-primary btn-sel-personero" data-id="' + row.mesa_id + '"><i class="fas fa-check me-1"></i>Seleccionar</button>';
                                  } },
                            ],
                            order: [[0, 'asc']], pageLength: 10,
                        });
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersoneros')).show();
                    setTimeout(function () { tablaPersoneros.columns.adjust(); }, 300);
                });
                $(document).on('click', '.btn-sel-personero', function () {
                    const mesaId = $(this).data('id');
                    limpiarMesa();
                    $.getJSON(urlConteo + '/' + mesaId + '/conteo')
                        .done(function (data) {
                            $dni.val(data.mesa_numero ?? '');
                            fijarMesa(data);
                            pintarResultado('success', 'Mesa seleccionada: <strong>' + data.mesa + '</strong>. Se muestran sus votos registrados.');
                        })
                        .fail(function () {
                            pintarResultado('danger', 'No se pudo cargar la mesa seleccionada.');
                        });
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersoneros')).hide();
                });
            }

            $form.on('submit', function () {
                if ($mesaId.length && !$mesaId.val()) {
                    pintarResultado('danger', 'Debe elegir la mesa por su número antes de guardar.');
                    if (!mesaBloqueada) { $dni.focus(); }
                    return false;
                }
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection