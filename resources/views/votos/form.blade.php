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
    $personaSeleccionada = $personaSeleccionada ?? $personeroFijo?->persona;
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
                    <div class="col-12 col-md-4 mb-3">
                        <label for="dni_buscar" class="form-label"><strong>DNI del personero</strong></label>
                        @if ( $esPersonero )
                            <input type="text" class="form-control" id="dni_buscar" readonly
                                   value="{{ $personeroFijo?->persona?->dni }}">
                        @else
                            <div class="input-group">
                                <input type="text" class="form-control" id="dni_buscar" name="dni_buscar"
                                       value="{{ old('dni_buscar', $personaSeleccionada->dni ?? '') }}"
                                       placeholder="Ingrese el DNI" spellcheck="false" autocorrect="off"
                                       autocapitalize="off" autocomplete="off" inputmode="numeric" maxlength="20">
                                <button type="button" class="btn btn-outline-primary" id="btnModalPersoneros"
                                        data-bs-toggle="tooltip" title="Buscar personero en el listado">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a class="btn btn-outline-success" id="btnRegistrarPersonero"
                                   href="{{ route('personeros.create') }}" style="display: none;"
                                   data-bs-toggle="tooltip" title="Registrar personero nuevo">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="persona_nombre" class="form-label"><strong>Nombres</strong></label>
                        <input type="text" class="form-control" id="persona_nombre" readonly
                               value="{{ old('persona_nombre', $personaSeleccionada?->apellidoNombre() ?? '') }}"
                               placeholder="Se completa al buscar el personero">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>Escriba el DNI o pulse el ícono de búsqueda para elegir un personero del listado.
                        </div>
                    </div>
                </div>
                <input type="hidden" name="personero_id" id="personero_id"
                       value="{{ old('personero_id', $personeroSelect?->personero_id ?? $personeroFijo?->personero_id) }}">
                <div id="resultadoPersonero" class="mb-3"></div>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label"><strong>Mesa de votación</strong></label>
                        <input type="text" class="form-control" readonly id="mesa_personero"
                               value="{{ $personeroSelect?->mesa?->etiqueta() ?? $personeroFijo?->mesa?->etiqueta() }}">
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

@if ( ! $esPersonero )
<div class="modal fade" id="modalPersoneros" tabindex="-1" aria-labelledby="modalPersonerosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPersonerosLabel">
                    <i class="fas fa-user-check me-2"></i>Seleccionar personero
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover w-100" id="tabla-modal-personeros">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Personero</th>
                                <th>Mesa</th>
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

@csrf

@section('footer')
    <script>
        $(document).ready(function () {
            const urlBuscar = "{{ route('votos.buscarPersonero') }}";
            const urlConteo = "{{ url('votos/personero') }}";
            const $form = $('form').first();
            const $dni = $('#dni_buscar');
            const $nombre = $('#persona_nombre');
            const $personero = $('#personero_id');
            const $mesa = $('#mesa_personero');
            const $resultado = $('#resultadoPersonero');
            const $btnRegistrar = $('#btnRegistrarPersonero');

            const pintarConteos = function (conteos) {
                $('input[name^="votos["]').each(function () {
                    const nombre = $(this).attr('name') || '';
                    const partidoId = nombre.replace(/[^0-9]/g, '');
                    $(this).val((conteos && conteos[partidoId] !== undefined) ? conteos[partidoId] : 0);
                });
            };

            // Resetea el formulario inferior (mesa + votos) al cambiar de personero.
            const resetearConteo = function () { $mesa.val(''); pintarConteos({}); };
            const pintarResultado = function (tipo, mensaje) {
                if (!$resultado.length) { return; }
                $resultado.html('<p class="alert alert-' + tipo + ' py-2 mb-0">' + mensaje + '</p>');
            };
            const fijarPersonero = function (personeroId, nombre, mesa, conteos) {
                $personero.val(personeroId); $nombre.val(nombre || '');
                $mesa.val(mesa || ''); pintarConteos(conteos || {});
            };
            const limpiarPersonero = function () {
                $personero.val(''); $nombre.val(''); resetearConteo();
            };

            if ($dni.length && !$dni.prop('readonly')) {
                const normalizar = function (v) { return (v || '').replace(/\D/g, ''); };
                let temporizador = null;
                const buscar = function () {
                    const dni = normalizar($dni.val());
                    limpiarPersonero(); $btnRegistrar.hide();
                    if (dni.length < 8) {
                        pintarResultado('info', 'Ingrese al menos 8 dígitos del DNI para buscar el personero.');
                        return;
                    }
                    $.getJSON(urlBuscar, { dni: dni })
                        .done(function (data) {
                            fijarPersonero(data.personero_id, data.nombre_completo, data.mesa, data.conteos);
                            pintarResultado('success', 'Personero encontrado: <strong>' + data.nombre_completo + '</strong> (DNI: ' + data.dni + '). Se muestran sus votos registrados.');
                        })
                        .fail(function (xhr) {
                            const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : 'No fue posible realizar la búsqueda.';
                            pintarResultado('danger', mensaje);
                            if (xhr.status === 404) { $btnRegistrar.show(); }
                        });
                };
                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    limpiarPersonero(); $btnRegistrar.hide();
                    if (normalizar($dni.val()).length >= 8) { temporizador = setTimeout(buscar, 300); }
                    else { pintarResultado('info', 'Ingrese el DNI del personero para buscarlo.'); }
                });
                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); buscar(); }
                });
                let tablaPersoneros = null;
                $('#btnModalPersoneros').on('click', function () {
                    limpiarPersonero(); $btnRegistrar.hide();
                    pintarResultado('info', 'Elija un personero del listado para ver sus votos registrados.');
                    if (!tablaPersoneros) {
                        tablaPersoneros = $('#tabla-modal-personeros').DataTable({
                            processing: true, serverSide: true,
                            language: { url: "{{ asset('datatables/spanish.json') }}" },
                            ajax: { url: "{{ route('votos.personerosModal') }}" },
                            columns: [
                                { data: 'dni', name: 'personas.dni' },
                                { data: 'persona', orderable: false, searchable: false },
                                { data: 'mesa', orderable: false, searchable: false },
                                { data: null, orderable: false, searchable: false, className: 'text-end',
                                  render: function (data, type, row) {
                                      return '<button type="button" class="btn btn-sm btn-primary btn-sel-personero" data-id="' + row.personero_id + '"><i class="fas fa-check me-1"></i>Seleccionar</button>';
                                  } },
                            ],
                            order: [[0, 'asc']], pageLength: 10,
                        });
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersoneros')).show();
                    setTimeout(function () { tablaPersoneros.columns.adjust(); }, 300);
                });
                $(document).on('click', '.btn-sel-personero', function () {
                    const personeroId = $(this).data('id');
                    const celdas = $(this).closest('tr').find('td');
                    const dni = $(celdas[0]).text().trim();
                    $dni.val(dni === '—' ? '' : dni);
                    limpiarPersonero(); $btnRegistrar.hide();
                    $.getJSON(urlConteo + '/' + personeroId + '/conteo')
                        .done(function (data) {
                            $.getJSON(urlBuscar, { dni: $dni.val() })
                                .done(function (detalle) {
                                    fijarPersonero(detalle.personero_id, detalle.nombre_completo, detalle.mesa, detalle.conteos);
                                    pintarResultado('success', 'Personero seleccionado: <strong>' + detalle.nombre_completo + '</strong>. Se muestran sus votos registrados.');
                                })
                                .fail(function () {
                                    pintarResultado('danger', 'No se pudo cargar el personero seleccionado.');
                                });
                        });
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersoneros')).hide();
                });
            }

            $form.on('submit', function () {
                if ($personero.length && !$personero.val()) {
                    pintarResultado('danger', 'Debe buscar al personero por su DNI antes de guardar.');
                    $dni.focus();
                    return false;
                }
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection