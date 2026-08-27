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
                            <label for="persona_id" class="form-label"><strong>Persona</strong></label>
                            <select class="form-select" name="persona_id" id="persona_id" required>
                                <option value="">Seleccione una persona (solo las que aún no tienen cuenta)</option>
                                @forelse ( $personas as $persona )
                                    <option value="{{ $persona->persona_id }}" @if ( old('persona_id', $usuario->persona_id) == $persona->persona_id ) selected @endif>
                                        {{ $persona->apellidoNombre() }} — DNI: {{ $persona->dni }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay personas disponibles sin cuenta</option>
                                @endforelse
                            </select>
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
            $('form').on('submit', function () {
                $('button[type=submit]').prop('disabled', true);
            });
        });
    </script>
@endsection