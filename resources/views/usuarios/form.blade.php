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
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-nurse me-2"></i><strong>Datos de la cuenta</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        @if ( empty( $usuario->usuario_id ) )
                            <label for="buscarDni" class="form-label"><strong>DNI del usuario</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscarDni" name="dni_buscar"
                                       placeholder="Ingrese el DNI de la persona (ej. 12345678)" spellcheck="false"
                                       autocorrect="off" autocapitalize="off" autocomplete="off"
                                       inputmode="numeric" maxlength="20" aria-describedby="btnBuscarDni">
                                <button type="button" class="btn btn-outline-primary" id="btnBuscarDni"
                                        data-bs-toggle="tooltip" title="Buscar persona por DNI">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>Al ingresar el DNI se buscará a la persona; si existe, se mostrará su nombre completo para agregarla como nuevo usuario.
                            </div>
                            <input type="hidden" name="persona_id" id="persona_id" value="{{ old('persona_id') }}">
                            <div id="resultadoPersona" class="mt-2">
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
                @forelse ( $menus as $menu )
                    <div class="mb-3">
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

    <div class="col-lg-4">
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

@csrf

@section('footer')
    <script>
        $(document).ready(function () {
            const $form = $('form');
            const $dni = $('#buscarDni');
            const $resultado = $('#resultadoPersona');
            const $personaId = $('#persona_id');

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
                let temporizador = null;

                const normalizarDni = function (valor) {
                    return (valor || '').replace(/\D/g, '');
                };

                const buscarPersona = function () {
                    const dni = normalizarDni($dni.val());

                    if (dni.length < 8) {
                        $personaId.val('');
                        pintarResultado('info', 'Ingrese al menos 8 dígitos del DNI para buscar la persona.');
                        return;
                    }

                    $.getJSON('{{ route('usuarios.buscarPersona') }}', { dni: dni })
                        .done(function (data) {
                            $personaId.val(data.persona_id);
                            pintarResultado(
                                'success',
                                'Persona encontrada: <strong>' + data.nombre_completo + '</strong> (DNI: ' + data.dni + '). Se agregará como nuevo usuario.'
                            );
                        })
                        .fail(function (xhr) {
                            $personaId.val('');
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
                        $personaId.val('');
                        pintarResultado('info', 'Ingrese el DNI de la persona para buscarla.');
                    }
                });

                $dni.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        buscarPersona();
                    }
                });

                $('#btnBuscarDni').on('click', buscarPersona);
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