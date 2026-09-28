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

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('js/select2.min.js') }}"></script>
@endpush

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-id-card me-2"></i><strong>Datos de la afiliación</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                {{-- Persona --}}
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="dni_buscar" class="form-label"><strong>DNI</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="dni_buscar" name="dni_buscar"
                                   value="{{ old('dni_buscar', $personaSeleccionada->dni ?? '') }}"
                                   placeholder="Ingrese el DNI" spellcheck="false" autocorrect="off"
                                   autocapitalize="off" autocomplete="off" inputmode="numeric" maxlength="20"
                                   @if ( $esEdicion ) readonly @endif>
                            <button type="button" class="btn btn-outline-primary" id="btnModalPersonas"
                                    data-bs-toggle="tooltip" title="Buscar persona en el listado"
                                    @if ( $esEdicion ) disabled @endif>
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="persona_nombre" class="form-label"><strong>Nombres</strong></label>
                        <input type="text" class="form-control" id="persona_nombre" readonly
                               value="{{ old('persona_nombre', $personaSeleccionada?->apellidoNombre() ?? '') }}"
                               placeholder="Se completa al buscar la persona">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>Escriba el DNI o use <strong>Buscar</strong> para elegir una persona del listado.
                        </div>
                    </div>
                </div>
                <input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id', $afiliado->persona_id) }}">
                <div id="resultadoPersona" class="mb-3"></div>

                {{-- Base --}}
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="base_buscar" class="form-label"><strong>Base</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="base_buscar" name="base_buscar"
                                   value="{{ old('base_buscar', $baseSeleccionada->descripcion ?? '') }}"
                                   placeholder="Descripción de la base" spellcheck="false" autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" id="btnModalBases"
                                    data-bs-toggle="tooltip" title="Buscar base en el listado">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="base_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="base_ubicacion" readonly
                               value="{{ old('base_ubicacion', $baseSeleccionada->ubicacion ?? '') }}"
                               placeholder="Se completa al buscar la base">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>Escriba la descripción o use <strong>Buscar</strong> para elegir una base del listado.
                        </div>
                    </div>
                </div>
                <input type="hidden" name="base_id" id="base_id" value="{{ old('base_id', $afiliado->base_id) }}">
                <div id="resultadoBase" class="mb-3"></div>

                {{-- Cargo --}}
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="cargo_id" class="form-label"><strong>Cargo</strong></label>
                        <select class="form-select" name="cargo_id" id="cargo_id">
                            <option value="">Seleccione un cargo</option>
                            @forelse ( $cargos as $cargo )
                                <option value="{{ $cargo->cargo_id }}"
                                        @if ( old( 'cargo_id', $afiliado->cargo_id ) == $cargo->cargo_id ) selected @endif>
                                    {{ $cargo->descripcion }}
                                </option>
                            @empty
                                <option value="" disabled>No hay cargos registrados</option>
                            @endforelse
                        </select>
                        <div class="form-text">Busque por descripción. Cargos del módulo <a href="{{ route('cargos.index') }}">Cargos</a>.</div>
                        <div id="resultadoCargo"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( $esEdicion )
                    <p class="small text-muted mb-1">Registrado: {{ $afiliado->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $afiliado->updated_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">
                        La persona no se modifica. Puede cambiar la base y el cargo de la afiliación.
                    </p>
                @else
                    <p class="small text-muted mb-3">
                        Seleccione la persona, la base y el cargo. Cada persona solo puede estar
                        afiliada a una base; para cambiarla, anule primero la afiliación actual.
                    </p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('afiliados.index') }}">
                        <i class="fas fa-arrow-left me-2"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@csrf

{{-- Modal de personas --}}
<div class="modal fade" id="modalPersonas" tabindex="-1" aria-labelledby="modalPersonasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPersonasLabel">
                    <i class="fas fa-user me-2"></i>Seleccionar persona
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    <i class="fas fa-info-circle me-1"></i>Solo se listan personas sin afiliación activa.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover w-100" id="tabla-modal-personas">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Persona</th>
                                <th>Teléfono</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de bases --}}
<div class="modal fade" id="modalBases" tabindex="-1" aria-labelledby="modalBasesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBasesLabel">
                    <i class="fas fa-database me-2"></i>Seleccionar base
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover w-100" id="tabla-modal-bases">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Ubicación</th>
                                <th class="text-center">Afiliados</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@section('footer')
    <script>
        $(document).ready(function () {
            const $form = $('#form-afiliado');
            if (!$form.length) {
                return;
            }

            // Cargo: dropdown con búsqueda (select2).
            $('#cargo_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione un cargo',
                allowClear: true,
                language: {
                    noResults: function () { return 'Sin resultados'; },
                    searching: function () { return 'Buscando...'; },
                },
            });

            const $resultadoPersona = $('#resultadoPersona');
            const $resultadoBase = $('#resultadoBase');

            const pintar = function ($contenedor, tipo, html) {
                if (!$contenedor.length) {
                    return;
                }
                if (tipo === 'success') {
                    $contenedor.html('<p class="alert alert-success py-2 mb-0"><i class="fas fa-user-check me-1"></i>' + html + '</p>');
                } else if (tipo === 'danger') {
                    $contenedor.html('<p class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + html + '</p>');
                } else {
                    $contenedor.html('<p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>' + html + '</p>');
                }
            };

            // Tablas de los modales: se inicializan la primera vez que se abren.
            const tablas = { personas: null, bases: null };

            const inicializarTablaPersonas = function () {
                if (tablas.personas) {
                    return;
                }
                tablas.personas = $('#tabla-modal-personas').DataTable({
                    processing: true,
                    serverSide: true,
                    language: { url: "{{ asset('datatables/spanish.json') }}" },
                    ajax: { url: "{{ route('afiliados.personas') }}" },
                    columns: [
                        { data: 'dni', name: 'dni' },
                        { data: 'persona', orderable: false, searchable: false },
                        { data: 'telefono', orderable: false, searchable: false,
                          render: function (data) { return data ? data : '—'; } },
                        { data: null, orderable: false, searchable: false, className: 'text-end',
                          render: function (data, type, row) {
                              return '<button type="button" class="btn btn-sm btn-primary btn-seleccionar-persona"' +
                                     ' data-persona-id="' + row.persona_id + '"' +
                                     ' data-dni="' + row.dni + '"' +
                                     ' data-nombre="' + row.persona + '">' +
                                     '<i class="fas fa-check me-1"></i>Seleccionar</button>';
                          } },
                    ],
                    order: [[0, 'asc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                });
            };

            const inicializarTablaBases = function () {
                if (tablas.bases) {
                    return;
                }
                tablas.bases = $('#tabla-modal-bases').DataTable({
                    processing: true,
                    serverSide: true,
                    language: { url: "{{ asset('datatables/spanish.json') }}" },
                    ajax: { url: "{{ route('afiliados.bases') }}" },
                    columns: [
                        { data: 'descripcion', name: 'descripcion' },
                        { data: 'ubicacion', name: 'ubicacion',
                          render: function (data) { return data ? data : '—'; } },
                        { data: 'afiliados_count', orderable: false, searchable: false, className: 'text-center',
                          render: function (data) { return data ? data : 0; } },
                        { data: null, orderable: false, searchable: false, className: 'text-end',
                          render: function (data, type, row) {
                              return '<button type="button" class="btn btn-sm btn-primary btn-seleccionar-base"' +
                                     ' data-base-id="' + row.base_id + '"' +
                                     ' data-descripcion="' + row.descripcion + '"' +
                                     ' data-ubicacion="' + (row.ubicacion ? row.ubicacion : '') + '">' +
                                     '<i class="fas fa-check me-1"></i>Seleccionar</button>';
                          } },
                    ],
                    order: [[0, 'asc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                });
            };

            // Abrir los modales (se inicializan las tablas al primer uso).
            $('#btnModalPersonas').on('click', function () {
                inicializarTablaPersonas();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).show();
                setTimeout(function () { tablas.personas.columns.adjust(); }, 300);
            });

            $('#btnModalBases').on('click', function () {
                inicializarTablaBases();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBases')).show();
                setTimeout(function () { tablas.bases.columns.adjust(); }, 300);
            });

            // Búsqueda de persona por DNI.
            const $dni = $('#dni_buscar');
            const $personaId = $('#persona_id');
            const $personaNombre = $('#persona_nombre');
            const $resultadoCargo = $('#resultadoCargo');

            const establecerPersona = function (id, dni, nombre) {
                $personaId.val(id);
                $dni.val(dni);
                $personaNombre.val(nombre);
            };

            const limpiarPersona = function () {
                $personaId.val('');
                $personaNombre.val('');
            };

            const buscarPersona = function () {
                const dni = ($dni.val() || '').replace(/\D/g, '');
                if (dni.length < 8) {
                    limpiarPersona();
                    pintar($resultadoPersona, 'info', 'Ingrese al menos 8 dígitos del DNI para buscar la persona.');
                    return;
                }
                $.getJSON("{{ route('afiliados.buscarPersona') }}", { dni: dni })
                    .done(function (data) {
                        establecerPersona(data.persona_id, data.dni, data.nombre_completo);
                        pintar($resultadoPersona, 'success', 'Persona seleccionada: <strong>' + data.nombre_completo + '</strong>');
                    })
                    .fail(function (xhr) {
                        limpiarPersona();
                        const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                            ? xhr.responseJSON.mensaje
                            : 'No fue posible realizar la búsqueda.';
                        pintar($resultadoPersona, 'danger', mensaje);
                    });
            };

            if (!$dni.prop('readonly')) {
                let temporizador = null;
                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    const dni = ($dni.val() || '').replace(/\D/g, '');
                    if (dni.length === 8) {
                        temporizador = setTimeout(buscarPersona, 300);
                    } else {
                        limpiarPersona();
                        pintar($resultadoPersona, 'info', 'Ingrese el DNI de la persona para buscarla.');
                    }
                });
                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        buscarPersona();
                    }
                });
            }

            // Selección desde el modal de personas.
            $(document).on('click', '.btn-seleccionar-persona', function () {
                const nombre = $(this).data('nombre');
                establecerPersona($(this).data('persona-id'), $(this).data('dni'), nombre);
                pintar($resultadoPersona, 'success', 'Persona seleccionada: <strong>' + nombre + '</strong>');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).hide();
            });

            // Búsqueda de base por descripción.
            const $baseDescripcion = $('#base_buscar');
            const $baseId = $('#base_id');
            const $baseUbicacion = $('#base_ubicacion');

            const establecerBase = function (id, descripcion, ubicacion) {
                $baseId.val(id);
                $baseDescripcion.val(descripcion);
                $baseUbicacion.val(ubicacion || '');
            };

            const limpiarBase = function () {
                $baseId.val('');
                $baseUbicacion.val('');
            };

            const buscarBase = function () {
                const descripcion = ($baseDescripcion.val() || '').trim();
                if (descripcion === '') {
                    limpiarBase();
                    pintar($resultadoBase, 'info', 'Ingrese la descripción de la base para buscarla.');
                    return;
                }
                $.getJSON("{{ route('afiliados.buscarBase') }}", { descripcion: descripcion })
                    .done(function (data) {
                        establecerBase(data.base_id, data.descripcion, data.ubicacion);
                        pintar($resultadoBase, 'success', 'Base seleccionada: <strong>' + data.descripcion + '</strong>');
                    })
                    .fail(function (xhr) {
                        limpiarBase();
                        const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                            ? xhr.responseJSON.mensaje
                            : 'No fue posible realizar la búsqueda.';
                        pintar($resultadoBase, 'danger', mensaje);
                    });
            };

            $baseDescripcion.on('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    buscarBase();
                }
            });

            // Selección desde el modal de bases.
            $(document).on('click', '.btn-seleccionar-base', function () {
                const descripcion = $(this).data('descripcion');
                establecerBase($(this).data('base-id'), descripcion, $(this).data('ubicacion'));
                pintar($resultadoBase, 'success', 'Base seleccionada: <strong>' + descripcion + '</strong>');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBases')).hide();
            });

            $('#cargo_id').on('change', function () {
                if ($(this).val()) {
                    $resultadoCargo.html('');
                }
            });

            // Validación previa al envío.
            $form.on('submit', function () {
                if (!$personaId.val()) {
                    pintar($resultadoPersona, 'danger', 'Debe seleccionar una persona antes de guardar.');
                    $dni.focus();
                    return false;
                }
                if (!$baseId.val()) {
                    pintar($resultadoBase, 'danger', 'Debe seleccionar una base antes de guardar.');
                    $baseDescripcion.focus();
                    return false;
                }
                if (!$('#cargo_id').val()) {
                    pintar($resultadoCargo, 'danger', 'Debe seleccionar un cargo antes de guardar.');
                    return false;
                }
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection

