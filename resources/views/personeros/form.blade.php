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
                <i class="fas fa-user-check me-2"></i><strong>Datos del personero</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="dni_buscar" class="form-label"><strong>DNI del personero</strong></label>
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
                                   href="{{ route('personas.create', ['origen' => 'personeros']) }}" style="display: none;"
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
                <input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id', $personero->persona_id) }}">
                <div id="resultadoPersona" class="mb-3"></div>

<div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="mesa_id" class="form-label"><strong>Mesa de votación</strong></label>
                        <select class="form-select" name="mesa_id" id="mesa_id" required>
                            <option value="">Seleccione una mesa</option>
                            @forelse ( $mesas as $mesa )
                                @php
                                    // Solo un apoderado por mesa: las ocupadas quedan deshabilitadas.
                                    $ocupada = (int) ( $mesa->personeros_count ?? 0 ) > 0;
                                    $esActual = old( 'mesa_id', $personero->mesa_id ) == $mesa->mesa_id;
                                @endphp
                                <option value="{{ $mesa->mesa_id }}"
                                        @if ( $esActual ) selected @endif
                                        @if ( $ocupada && ! $esActual ) disabled @endif>
                                    {{ $mesa->etiqueta() }}{{ $mesa->centro ? ' — '.$mesa->centro->descripcion : '' }}{{ $ocupada && ! $esActual ? ' (ya tiene personero)' : '' }}
                                </option>
                            @empty
                                <option value="" disabled>No hay mesas registradas</option>
                            @endforelse
                        </select>
                        <div class="form-text">
                            Cada mesa solo puede tener un personero. Mesas del módulo
                            <a href="{{ route('mesas.index') }}">Mesas de votación</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( $esEdicion )
                    <p class="small text-muted mb-1">Registrado: {{ $personero->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $personero->updated_at?->format('d/m/Y H:i') }}</p>
                @else
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="crear_usuario" name="crear_usuario"
                               @checked(old('crear_usuario', false))>
                        <label class="form-check-label" for="crear_usuario">
                            <strong>Crear usuario al personero</strong>
                        </label>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>Si se marca, se crea su cuenta usando el
                            <strong>DNI como usuario y contraseña</strong>, con acceso a
                            <strong>Gestión de votos</strong>. Por defecto está sin marcar.
                        </div>
                    </div>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('personeros.index') }}">
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
                    <i class="fas fa-info-circle me-1"></i>Solo se listan personas que aún no son personeros.
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
            const $form = $('#form-personero');
            if (!$form.length) {
                return;
            }

            const $resultadoPersona = $('#resultadoPersona');
            const $dni = $('#dni_buscar');
            const $personaId = $('#persona_id');
            const $personaNombre = $('#persona_nombre');

            const pintar = function (tipo, html) {
                if (tipo === 'success') {
                    $resultadoPersona.html('<p class="alert alert-success py-2 mb-0"><i class="fas fa-user-check me-1"></i>' + html + '</p>');
                } else if (tipo === 'danger') {
                    $resultadoPersona.html('<p class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + html + '</p>');
                } else {
                    $resultadoPersona.html('<p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>' + html + '</p>');
                }
            };

            // Botón "+" para registrar la persona cuando el DNI no existe.
            const $btnRegistrarPersona = $('#btnRegistrarPersona');
            const urlRegistroPersona = '{{ route('personas.create', ['origen' => 'personeros']) }}';

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

            // Solo el formulario de creación incluye el buscador por DNI.
            if ($dni.length && !$dni.prop('readonly')) {
                let temporizador = null;

                const buscarPersona = function () {
                    const dni = ($dni.val() || '').replace(/\D/g, '');

                    if (dni.length < 8) {
                        limpiarPersona();
                        pintar('info', 'Ingrese al menos 8 dígitos del DNI para buscar la persona.');
                        return;
                    }

                    $.getJSON('{{ route('personeros.buscarPersona') }}', { dni: dni })
                        .done(function (data) {
                            ocultarBtnRegistrar();
                            establecerPersona(data.persona_id, data.dni, data.nombre_completo);
                            pintar('success', 'Persona seleccionada: <strong>' + data.nombre_completo + '</strong> (DNI: ' + data.dni + ').');
                        })
                        .fail(function (xhr) {
                            limpiarPersona();
                            const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                                ? xhr.responseJSON.mensaje
                                : 'No fue posible realizar la búsqueda. Intente nuevamente.';
                            pintar('danger', mensaje);
                            // Si el DNI no existe se ofrece registrar la persona.
                            if (xhr.status === 404) {
                                mostrarBtnRegistrar(dni);
                            }
                        });
                };

                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    const dni = ($dni.val() || '').replace(/\D/g, '');
                    if (dni.length === 8) {
                        temporizador = setTimeout(buscarPersona, 300);
                    } else {
                        limpiarPersona();
                        pintar('info', 'Ingrese el DNI de la persona para buscarla.');
                    }
                });

                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        buscarPersona();
                    }
                });

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
                        ajax: { url: "{{ route('personeros.personas') }}" },
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

                $('#btnModalPersonas').on('click', function () {
                    inicializarTablaPersonas();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).show();
                    setTimeout(function () { tablaPersonas.columns.adjust(); }, 300);
                });

                // Selección desde el modal de personas.
                $(document).on('click', '.btn-seleccionar-persona', function () {
                    const nombre = $(this).data('nombre');
                    establecerPersona($(this).data('persona-id'), $(this).data('dni'), nombre);
                    pintar('success', 'Persona seleccionada: <strong>' + nombre + '</strong>.');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPersonas')).hide();
                });
            }

            $form.on('submit', function () {
                if ($dni.length && !$dni.prop('readonly') && !$personaId.val()) {
                    pintar('danger', 'Debe buscar a la persona por su DNI antes de guardar.');
                    $dni.focus();
                    return false;
                }
                if (!$('#mesa_id').val()) {
                    alert('Debe seleccionar una mesa de votación.');
                    return false;
                }
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection