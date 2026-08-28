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
                <i class="fas fa-user me-2"></i><strong>Datos de la persona</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="dni" class="form-label"><strong>DNI</strong></label>
                        <input type="text" class="form-control" id="dni" name="dni"
                               value="{{ old('dni', $persona->dni) }}" maxlength="255" spellcheck="false"
                               autocorrect="off" autocapitalize="off" required>
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="nombres" class="form-label"><strong>Nombres</strong></label>
                        <input type="text" class="form-control" id="nombres" name="nombres"
                               value="{{ old('nombres', $persona->nombres) }}" maxlength="255" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="primer_apellido" class="form-label"><strong>Primer apellido</strong></label>
                        <input type="text" class="form-control" id="primer_apellido" name="primer_apellido"
                               value="{{ old('primer_apellido', $persona->primer_apellido) }}" maxlength="255" required>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="segundo_apellido" class="form-label">Segundo apellido</label>
                        <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido"
                               value="{{ old('segundo_apellido', $persona->segundo_apellido) }}" maxlength="255">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                               value="{{ old('fecha_nacimiento', optional($persona->fecha_nacimiento)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                               value="{{ old('telefono', $persona->telefono) }}" maxlength="255" spellcheck="false">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="codigo_mesa" class="form-label">Código de mesa</label>
                        <input type="text" class="form-control" id="codigo_mesa" name="codigo_mesa"
                               value="{{ old('codigo_mesa', $persona->codigo_mesa) }}" maxlength="255" spellcheck="false">
                        <div class="form-text">Opcional. Identificador de la mesa donde emite su voto.</div>
                    </div>
                    <div class="col-12 col-md-6 mb-3 d-flex align-items-end">
                        <p class="text-muted small mb-0 mb-3">
                            <i class="fas fa-info-circle me-1"></i>La persona podrá asociarse a una cuenta de
                            usuario y a afiliaciones desde los procesos correspondientes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( ! empty( $persona->persona_id ) )
                    <p class="small text-muted mb-1">Registrado: {{ $persona->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $persona->updated_at?->format('d/m/Y H:i') }}</p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('personas.index') }}">
                        <i class="fas fa-arrow-left me-2"></i>Ver todas
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