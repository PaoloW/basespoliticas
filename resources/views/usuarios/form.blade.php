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

<div class="row">
    <div class="col-lg-8 col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-nurse me-2"></i><strong>Datos de la cuenta</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        @if ( empty( $usuario->usuario_id ) )
                            <div class="row">
                                <div class="col-12 col-md-4 mb-3">
                                    <label for="dni_buscar" class="form-label"><strong>DNI del usuario</strong></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="dni_buscar" name="dni_buscar"
                                               value="{{ old('dni_buscar', $personaSeleccionada->dni ?? '') }}"
                                               placeholder="Ingrese el DNI" spellcheck="false" autocorrect="off"
                                               autocapitalize="off" autocomplete="off" inputmode="numeric" maxlength="20">
                                        <button type="button" class="btn btn-outline-primary" id="btnModalPersonas"
                                                data-bs-toggle="tooltip" title="Buscar persona en el listado">
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
                            <input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id') }}">
                            <div id="resultadoPersona" class="mb-3">
                                @if ( ! empty( $personaSeleccionada ?? null ) )
                                    <p class="alert alert-success py-2 mb-0">
                                        <i class="fas fa-user-check me-1"></i>
                                        Persona seleccionada: <strong>{{ $personaSeleccionada->apellidoNombre() }}</strong>
                                        (DNI: {{ $personaSeleccionada->dni }}).
                                    </p>
                                @endif
                            </div>
                        @else
                            <label for="persona_nombre" class="form-label"><strong>Persona</strong></label>
                            <input type="text" class="form-control" value="{{ $usuario->persona?->apellidoNombre() }} (DNI: {{ $usuario->persona?->dni }})" disabled>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        @if ( empty( $usuario->usuario_id ) )
                            <label for="clave" class="form-label"><strong>Crear contraseña</strong></label>
                        @else
                            <label for="clave" class="form-label">Cambiar contraseña <small class="text-muted">(dejar vacío para no modificarla)</small></label>
                        @endif
                        <div class="input-group">
                            <input type="password" class="form-control" id="clave" name="clave"
                                   placeholder="Contraseña" spellcheck="false" autocorrect="off"
                                   autocapitalize="off" autocomplete="new-password" aria-describedby="btnClave"
                                   @if ( empty( $usuario->usuario_id ) ) required @endif>
                            <button class="btn btn-outline-secondary btn-toggle-password" type="button"
                                    id="btnClave" data-target="clave" data-bs-toggle="tooltip" title="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tasks me-2"></i><strong>Accesos (menús)</strong>
                <p class="text-muted small mb-1"><em>Seleccione los menús a los que tendrá acceso el usuario</em></p>
            </div>
            <div class="card-body">
                <div class="grid-container">
                    @forelse ( $menus as $menu )
                        <div class="menu-group">
                            <div class="fw-bold mb-1"><i class="{{ $menu->icono }} me-1"></i>{{ $menu->descripcion }}</div>
                            @foreach ( $menu->hijos as $hijo )
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="menu_ids[]"
                                           id="menu_{{ $hijo->menu_id }}" value="{{ $hijo->menu_id }}"
                                           @if ( in_array( (int) $hijo->menu_id, (array) $menu_ids, true ) ) checked @endif>
                                    <label class="form-check-label" for="menu_{{ $hijo->menu_id }}">
                                        <i class="{{ $hijo->icono }} me-1"></i>{{ $hijo->descripcion }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-muted">No hay menús registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( ! empty( $usuario->usuario_id ) )
                    <p class="small text-muted mb-1">Registrado: {{ $usuario->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $usuario->updated_at?->format('d/m/Y H:i') }}</p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-arrow-left me-2"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if ( empty( $usuario->usuario_id ) )
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
                    <i class="fas fa-info-circle me-1"></i>Solo se listan personas sin cuenta de usuario.
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
@endif

@csrf

@section('footer')
    <script>
        $(document).ready(function () {
            const $form = $('form');
            const $dni = $('#dni_buscar');
            const $resultado = $('#resultadoPersona');
            const $personaId = $('#persona_id');
            const $personaNombre = $('#persona_nombre');

            // Tabla del modal: se inicializa la primera vez que se abre.
            let tablaPersonas = null;
            const inicializarTablaPersonas = function () {
                if (tablaPersonas) {
                    return;
                }
                tablaPersonas = $('#tabla-modal-personas').DataTable({
                    processing: true,
                    serverSide: true,
                    language: { url: "{{ asset('datatables/spanish.json') }}" },
                    ajax: { url: "{{ route('usuarios.personas') }}" },
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

            const pintarResultado = function (tipo, html) {
                if (!$resultado.length) {
                    return;
                }
                if (tipo === 'success') {
                    $resultado.html('<p class="alert alert-success py-2 mb-0"><i class="fas fa-user-check me-1"></i>' + html + '</p>');
                } else if (tipo === 'danger') {
                    $resultado.html('<p class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + html + '</p>');
                } else {
                    $resultado.html('<p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>' + html + '</p>');
                }
            };

            // Solo el formulario de creación incluye el buscador por DNI.
            if ($dni.length) {
                const establecerPersona = function (id, dni, nombre) {
                    $personaId.val(id);
                    $dni.val(dni);
                    $personaNombre.val(nombre);
                };

                const limpiarPersona = function () {
                    $personaId.val('');
                    $personaNombre.val('');
                };

                let temporizador = null;

                const normalizarDni = function (valor) {
                    return (valor || '').replace(/\D/g, '');
                };

                const buscarPersona = function () {
                    const dni = normalizarDni($dni.val());

                    if (dni.length < 8) {
                        limpiarPersona();
                        pintarResultado('info', 'Ingrese al menos 8 dígitos del DNI para buscar la persona.');
                        return;
                    }

                    $.getJSON('{{ route('usuarios.buscarPersona') }}', { dni: dni })
                        .done(function (data) {
                            establecerPersona(data.persona_id, data.dni, data.nombre_completo);
                            pintarResultado(
                                'success',
                                'Persona encontrada: <strong>' + data.nombre_completo + '</strong> (DNI: ' + data.dni + '). Se agregará como nuevo usuario.'
                            );
                        })
                        .fail(function (xhr) {
                            limpiarPersona();
                            const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                                ? xhr.responseJSON.mensaje
                                : 'No fue posible realizar la búsqueda. Intente nuevamente.';
                            pintarResultado('danger', mensaje);
                        });
                };

                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    const dni = normalizarDni($dni.val());
                    if (dni.length === 8) {
                        temporizador = setTimeout(buscarPersona, 300);
                    } else {
                        limpiarPersona();
                        pintarResultado('info', 'Ingrese el DNI de la persona para buscarla.');
                    }
                });

                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        buscarPersona();
                    }
                });

                // Abrir el modal de personas (se inicializa la tabla al primer uso).
                $('#btnModalPersonas').on('click', function () {
                    inicializarTablaPersonas();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).show();
                    setTimeout(function () { tablaPersonas.columns.adjust(); }, 300);
                });

                // Selección desde el modal de personas.
                $(document).on('click', '.btn-seleccionar-persona', function () {
                    const nombre = $(this).data('nombre');
                    establecerPersona($(this).data('persona-id'), $(this).data('dni'), nombre);
                    pintarResultado('success', 'Persona seleccionada: <strong>' + nombre + '</strong>.');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).hide();
                });
            }

            $form.on('submit', function () {
                if ($dni.length && !$personaId.val()) {
                    pintarResultado('danger', 'Debe buscar a la persona por su DNI antes de guardar.');
                    $dni.focus();
                    return false;
                }
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection