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
                                   autocapitalize="off" autocomplete="off" inputmode="numeric" maxlength="8"
                                   @if ( $esEdicion ) readonly @endif>
                            <button type="button" class="btn btn-outline-primary" id="btnModalPersonas"
                                    data-bs-toggle="tooltip" title="Buscar persona en el listado"
                                    @if ( $esEdicion ) disabled @endif>
                                <i class="fas fa-search"></i>
                            </button>
                            @unless ( $esEdicion )
                                {{-- Se muestra solo cuando el DNI consultado no existe --}}
                                <a class="btn btn-outline-success" id="btnRegistrarPersona"
                                   href="{{ route('personas.create', ['origen' => 'afiliados']) }}" style="display: none;"
                                   data-bs-toggle="tooltip" title="Registrar persona nueva con este DNI">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endunless
                        </div>
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="persona_nombre" class="form-label"><strong>Nombres</strong></label>
                        <input type="text" class="form-control" id="persona_nombre" readonly
                               value="{{ old('persona_nombre', $personaSeleccionada?->apellidoNombre() ?? '') }}"
                               placeholder="Se completa al buscar la persona">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>Escriba el DNI o pulse el ícono de búsqueda para elegir una persona del listado.
                        </div>
                    </div>
                </div>
                <input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id', $afiliado->persona_id) }}">
                <div id="resultadoPersona" class="mb-3"></div>

                {{-- Base --}}
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="base_id" class="form-label"><strong>Base</strong></label>
                        <select class="form-select" name="base_id" id="base_id">
                            <option value="">Seleccione una base</option>
                            @forelse ( $bases as $base )
                                <option value="{{ $base->base_id }}"
                                        @if ( old( 'base_id', $afiliado->base_id ) == $base->base_id ) selected @endif>
                                    {{ $base->descripcion }}{{ $base->ubicacion ? ' ('.$base->ubicacion.')' : '' }}
                                </option>
                            @empty
                                <option value="" disabled>No hay bases registradas</option>
                            @endforelse
                        </select>
                        <div class="form-text">Bases del módulo <a href="{{ route('bases.index') }}">Bases</a>.</div>
                        <div id="resultadoBase"></div>
                    </div>
                </div>

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

@unless ( $esEdicion )
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
@endunless



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

            // Base: dropdown con búsqueda (select2).
            $('#base_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione una base',
                allowClear: true,
                language: {
                    noResults: function () { return 'Sin resultados'; },
                    searching: function () { return 'Buscando...'; },
                },
            });

            const $resultadoPersona = $('#resultadoPersona');
            const $resultadoBase = $('#resultadoBase');
            const $resultadoCargo = $('#resultadoCargo');
            const $dni = $('#dni_buscar');
            const $personaId = $('#persona_id');
            const $personaNombre = $('#persona_nombre');

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
            const tablas = { personas: null };

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

            // Abrir el modal de personas (se inicializa la tabla al primer uso).
            $('#btnModalPersonas').on('click', function () {
                inicializarTablaPersonas();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).show();
                setTimeout(function () { tablas.personas.columns.adjust(); }, 300);
            });

            // Búsqueda de persona por DNI.
            const $btnRegistrarPersona = $('#btnRegistrarPersona');
            const urlRegistroPersona = '{{ route('personas.create', ['origen' => 'afiliados']) }}';

            const mostrarBtnRegistrar = function (dni) {
                if (!$btnRegistrarPersona.length) {
                    return;
                }
                $btnRegistrarPersona
                    .attr('href', urlRegistroPersona + '&dni=' + encodeURIComponent(dni))
                    .show();
            };

            const ocultarBtnRegistrar = function () {
                $btnRegistrarPersona.hide();
            };

            const establecerPersona = function (id, dni, nombre) {
                $personaId.val(id);
                $dni.val(dni);
                $personaNombre.val(nombre);
            };

            const limpiarPersona = function () {
                $personaId.val('');
                $personaNombre.val('');
                ocultarBtnRegistrar();
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
                        ocultarBtnRegistrar();
                        establecerPersona(data.persona_id, data.dni, data.nombre_completo);
                        pintar($resultadoPersona, 'success', 'Persona seleccionada: <strong>' + data.nombre_completo + '</strong>');
                    })
                    .fail(function (xhr) {
                        limpiarPersona();
                        const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                            ? xhr.responseJSON.mensaje
                            : 'No fue posible realizar la búsqueda.';
                        pintar($resultadoPersona, 'danger', mensaje);
                        // Si el DNI no existe se ofrece registrar la persona.
                        if (xhr.status === 404) {
                            mostrarBtnRegistrar(dni);
                        }
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
                if (!$('#base_id').val()) {
                    pintar($resultadoBase, 'danger', 'Debe seleccionar una base antes de guardar.');
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

