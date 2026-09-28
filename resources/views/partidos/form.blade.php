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
                <i class="fas fa-flag me-2"></i><strong>Datos del partido</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="nombre" class="form-label"><strong>Nombre del partido</strong></label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               value="{{ old('nombre', $partido->nombre) }}" maxlength="255" required>
                        <div class="form-text">Nombre del partido político, por ejemplo: Partido Azul.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="logo" class="form-label"><strong>Logo del partido</strong></label>
                        <div class="d-flex align-items-center gap-3">
                            @if (!empty($partido->logo))
                                <img src="{{ asset($partido->logo) }}" alt="Logo actual" class="img-thumbnail" style="height: 38px; width: auto;">
                            @endif
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        </div>
                        <div class="form-text">Imagen opcional (máx. 2 MB). Se muestra en el registro de votos.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i><strong>Acciones</strong></div>
            <div class="card-body">
                @if ( ! empty( $partido->partido_id ) )
                    <p class="small text-muted mb-1">Registrado: {{ $partido->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $partido->updated_at?->format('d/m/Y H:i') }}</p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('partidos.index') }}">
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
