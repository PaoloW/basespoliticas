{{--
    Selector unificado de persona (registro de personeros y conteo de votos).
    La persona es el dato de origen; su afiliación es opcional y solo se informa.

    Variables esperadas:
    - $spOrigen    : módulo de origen ('personeros' | 'votos') para volver al registrar la persona.
    - $spUrlBuscar : ruta AJAX de búsqueda por DNI.
    - $spUrlModal  : ruta DataTable del listado paginado de personas.
    Opcionales: $spPersona, $spDni, $spBloquear, $spExtra, $spEtiqueta,
    $spEtiquetaNombre, $spAyuda, $spNotaModal, $spNombrePlaceholder.
--}}
@php
    $spPersona = $spPersona ?? null;
    $spDni = $spDni ?? $spPersona?->dni;
    $spBloquear = $spBloquear ?? false;
    $spExtra = $spExtra ?? [];
    $spEtiqueta = $spEtiqueta ?? 'DNI de la persona';
    $spEtiquetaNombre = $spEtiquetaNombre ?? 'Persona';
    $spAyuda = $spAyuda ?? 'Escriba el DNI o pulse el ícono de búsqueda para elegir una persona del listado.';
    $spNotaModal = $spNotaModal ?? 'Solo se listan personas que aún no son personeros. La afiliación se muestra si la persona la tiene (es opcional).';
    $spNombrePlaceholder = $spNombrePlaceholder ?? 'Se completa al buscar la persona';
    $spUrlNuevo = route('personas.create', array_merge(['origen' => $spOrigen], $spExtra));
@endphp

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label for="selector-persona-dni" class="form-label"><strong>{{ $spEtiqueta }}</strong></label>
        <div class="input-group">
            <input type="text" class="form-control" id="selector-persona-dni" name="dni_personero"
                   value="{{ old('dni_personero', $spDni) }}" placeholder="Ingrese el DNI"
                   spellcheck="false" autocorrect="off" autocapitalize="off" autocomplete="off"
                   inputmode="numeric" maxlength="8" @if ( $spBloquear ) readonly @endif>
            <button type="button" class="btn btn-outline-primary" id="selector-persona-btn-modal"
                    data-bs-toggle="tooltip" title="Buscar persona en el listado"
                    @if ( $spBloquear ) disabled @endif>
                <i class="fas fa-search"></i>
            </button>
            @unless ( $spBloquear )
                {{-- Se muestra solo cuando el DNI consultado no existe --}}
                <a class="btn btn-outline-success" id="selector-persona-btn-nuevo" href="{{ $spUrlNuevo }}"
                   style="display: none;" data-bs-toggle="tooltip" title="Registrar persona nueva con este DNI">
                    <i class="fas fa-plus"></i>
                </a>
            @endunless
        </div>
        <div class="form-text">
            <i class="fas fa-info-circle me-1"></i>{{ $spAyuda }}
        </div>
    </div>
    <div class="col-12 col-md-8 mb-3">
        <label for="selector-persona-nombre" class="form-label"><strong>{{ $spEtiquetaNombre }}</strong></label>
        <input type="text" class="form-control" id="selector-persona-nombre" readonly
               value="{{ old('persona_nombre', $spPersona?->apellidoNombre() ?? '') }}"
               placeholder="{{ $spNombrePlaceholder }}">
        <div class="form-text" id="selector-persona-afiliacion">
            <i class="fas fa-id-card me-1"></i>Afiliación:
            {{ $spPersona?->descripcionAfiliacion() ?? 'Sin afiliación' }}
        </div>
    </div>
</div>

<input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id', $spPersona?->persona_id) }}">
<div id="selector-persona-resultado" class="mb-3"></div>

{{-- Modal de personas --}}
<div class="modal fade" id="selector-persona-modal" tabindex="-1" aria-labelledby="selector-persona-modal-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selector-persona-modal-titulo">
                    <i class="fas fa-user me-2"></i>Seleccionar persona
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    <i class="fas fa-info-circle me-1"></i>{{ $spNotaModal }}
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover w-100" id="selector-persona-tabla">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Persona</th>
                                <th>Afiliación</th>
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

@push('scripts')
    <script>
        $(function () {
            const urlBuscar = "{{ $spUrlBuscar }}";
            const urlModal = "{{ $spUrlModal }}";
            const urlRegistro = "{{ $spUrlNuevo }}";
            const $dni = $('#selector-persona-dni');
            const $nombre = $('#selector-persona-nombre');
            const $afiliacion = $('#selector-persona-afiliacion');
            const $personaId = $('#persona_id');
            const $resultado = $('#selector-persona-resultado');
            const $btnNuevo = $('#selector-persona-btn-nuevo');
            const modal = function () {
                return bootstrap.Modal.getOrCreateInstance(document.getElementById('selector-persona-modal'));
            };

            const pintar = function (tipo, html) {
                $resultado.html('<p class="alert alert-' + tipo + ' py-2 mb-0">' + html + '</p>');
            };

            const pintarAfiliacion = function (afiliacion) {
                $afiliacion.html('<i class="fas fa-id-card me-1"></i>Afiliación: ' + (afiliacion || 'Sin afiliación'));
            };

            // Ofrece registrar la persona cuando el DNI no existe en el sistema.
            const mostrarBtnNuevo = function (dni) {
                if (!$btnNuevo.length) {
                    return;
                }
                $btnNuevo.attr('href', urlRegistro + '&dni=' + encodeURIComponent(dni)).show();
            };

            const ocultarBtnNuevo = function () {
                $btnNuevo.hide();
            };

            // Datos de la persona elegida: se guardan en el formulario y se avisa
            // al formulario contenedor (conteo de votos) mediante un evento.
            const establecerPersona = function (datos) {
                $personaId.val(datos.persona_id || '');
                $dni.val(datos.dni || '');
                $nombre.val(datos.nombre_completo || '');
                pintarAfiliacion(datos.afiliacion);
                $(document).trigger('persona:seleccionada', [datos]);
            };

            const limpiarPersona = function () {
                $personaId.val('');
                $nombre.val('');
                pintarAfiliacion('');
                ocultarBtnNuevo();
            };

            // El formulario contenedor puede pedir limpiar la selección (por
            // ejemplo al cambiar de mesa en el conteo de votos).
            $(document).on('persona:limpiar', function () {
                $dni.val('');
                limpiarPersona();
                $resultado.html('');
            });

            const buscar = function () {
                const dni = ($dni.val() || '').replace(/\D/g, '');

                if (dni.length < 8) {
                    limpiarPersona();
                    pintar('info', 'Ingrese los 8 dígitos del DNI para buscar la persona.');
                    return;
                }

                $.getJSON(urlBuscar, { dni: dni })
                    .done(function (data) {
                        ocultarBtnNuevo();
                        establecerPersona(data);
                        pintar('success', 'Persona seleccionada: <strong>' + data.nombre_completo + '</strong>'
                            + ' (DNI: ' + data.dni + '). Afiliación: ' + (data.afiliacion || 'Sin afiliación') + '.');
                    })
                    .fail(function (xhr) {
                        limpiarPersona();
                        const mensaje = (xhr.responseJSON && xhr.responseJSON.mensaje)
                            ? xhr.responseJSON.mensaje
                            : 'No fue posible realizar la búsqueda. Intente nuevamente.';
                        pintar('danger', mensaje);
                        if (xhr.status === 404) {
                            mostrarBtnNuevo(dni);
                        }
                    });
            };

            // Listado paginado del modal: se inicializa la primera vez que se abre.
            let tabla = null;
            $('#selector-persona-btn-modal').on('click', function () {
                // El modal se mueve al body: así se muestra aunque el selector esté
                // dentro de un contenedor oculto (bloque del personero en votos).
                const $modal = $('#selector-persona-modal');
                if ($modal.parent()[0] !== document.body) {
                    $modal.appendTo(document.body);
                }
                if (!tabla) {
                    tabla = $('#selector-persona-tabla').DataTable({
                        processing: true,
                        serverSide: true,
                        language: { url: "{{ asset('datatables/spanish.json') }}" },
                        ajax: { url: urlModal },
                        columns: [
                            { data: 'dni', name: 'dni' },
                            { data: 'persona', name: 'persona' },
                            { data: 'afiliacion', name: 'afiliacion' },
                            { data: 'telefono', name: 'telefono',
                              render: function (data) { return data ? data : '—'; } },
                            { data: null, orderable: false, searchable: false, className: 'text-end',
                              render: function (data, type, row) {
                                  const escapar = function (valor) {
                                      return String(valor === null || valor === undefined ? '' : valor).replace(/"/g, '&quot;');
                                  };
                                  return '<button type="button" class="btn btn-sm btn-primary selector-persona-elegir"' +
                                         ' data-persona-id="' + escapar(row.persona_id) + '"' +
                                         ' data-dni="' + escapar(row.dni) + '"' +
                                         ' data-nombre="' + escapar(row.persona) + '"' +
                                         ' data-afiliacion="' + escapar(row.afiliacion) + '">' +
                                         '<i class="fas fa-check me-1"></i>Seleccionar</button>';
                              } },
                        ],
                        order: [[0, 'asc']],
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                    });
                }
                modal().show();
                setTimeout(function () { tabla.columns.adjust(); }, 300);
            });

            // Selección desde el listado de personas.
            $(document).on('click', '.selector-persona-elegir', function () {
                const $boton = $(this);
                establecerPersona({
                    persona_id: $boton.data('persona-id'),
                    dni: $boton.data('dni'),
                    nombre_completo: $boton.data('nombre'),
                    afiliacion: $boton.data('afiliacion'),
                });
                pintar('success', 'Persona seleccionada: <strong>' + $boton.data('nombre') + '</strong>.');
                modal().hide();
            });

            if (!$dni.prop('readonly')) {
                let temporizador = null;
                $dni.on('input', function () {
                    clearTimeout(temporizador);
                    const dni = ($dni.val() || '').replace(/\D/g, '');
                    if (dni.length === 8) {
                        temporizador = setTimeout(buscar, 300);
                    } else {
                        limpiarPersona();
                        pintar('info', 'Ingrese el DNI de la persona para buscarla.');
                    }
                });
                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        buscar();
                    }
                });
            }

            // Al volver del alta de una persona nueva se retoma el proceso con la
            // persona ya seleccionada (el evento se emite al final, cuando los
            // formularios contenedores ya registraron sus manejadores).
            if ($personaId.val()) {
                setTimeout(function () {
                    const datos = {
                        persona_id: $personaId.val(),
                        dni: $dni.val(),
                        nombre_completo: $nombre.val(),
                        afiliacion: $afiliacion.text().replace('Afiliación:', '').trim(),
                    };
                    $(document).trigger('persona:seleccionada', [datos]);
                    pintar('success', 'Persona seleccionada: <strong>' + datos.nombre_completo + '</strong>.');
                }, 0);
            }
        });
    </script>
@endpush
