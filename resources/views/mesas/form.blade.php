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
                <i class="fas fa-table me-2"></i><strong>Datos de la mesa de votación</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="descripcion" class="form-label">Número de mesa</label>
                        <input type="number" class="form-control" id="descripcion" name="descripcion"
                               value="{{ old('descripcion', $mesa->descripcion) }}" min="1" step="1">
                        <div class="form-text">Opcional. Número de la mesa (p. ej. 001).</div>
                    </div>
                    <div class="col-12 col-md-8 mb-3">
                        <label for="centro_id" class="form-label"><strong>Centro de votación</strong></label>
                        <select class="form-select" name="centro_id" id="centro_id" required>
                            <option value="">Seleccione un centro de votación</option>
                            @forelse ( $centros as $centro )
                                <option value="{{ $centro->centro_id }}"
                                        @if ( old('centro_id', $mesa->centro_id) == $centro->centro_id ) selected @endif>
                                    {{ $centro->descripcion }}
                                </option>
                            @empty
                                <option value="" disabled>No hay centros registrados</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="votantes" class="form-label">Votantes</label>
                        <input type="number" class="form-control" id="votantes" name="votantes"
                               value="{{ old('votantes', $mesa->votantes) }}" min="0" step="1">
                        <div class="form-text">Opcional. Cantidad de votantes potenciales de la mesa.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( ! empty( $mesa->mesa_id ) )
                    <p class="small text-muted mb-1">Registrado: {{ $mesa->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $mesa->updated_at?->format('d/m/Y H:i') }}</p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('mesas.index') }}">
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