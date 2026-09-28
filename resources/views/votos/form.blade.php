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

@unless ( $esEdicion )
    @if ( $partidos->isEmpty() )
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-1"></i>No hay partidos registrados. Regístrelos en el módulo
            <a href="{{ route('partidos.index') }}" class="alert-link">Partidos</a>.
        </div>
    @endif
    @if ( $personas->isEmpty() )
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-1"></i>No hay personas registradas. Regístrelas en el módulo
            <a href="{{ route('personas.index') }}" class="alert-link">Personas</a>.
        </div>
    @endif
@endunless

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-vote-yea me-2"></i><strong>Datos del voto</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        @if ( $esEdicion )
                            <label class="form-label"><strong>Partido</strong></label>
                            <input type="text" class="form-control" readonly value="{{ $voto->partido?->nombre }}">
                        @else
                            <label for="partido_id" class="form-label"><strong>Partido</strong></label>
                            <select class="form-select" name="partido_id" id="partido_id" required>
                                <option value="">Seleccione un partido</option>
                                @forelse ( $partidos as $partido )
                                    <option value="{{ $partido->partido_id }}"
                                            @if ( old( 'partido_id', $voto->partido_id ) == $partido->partido_id ) selected @endif>
                                        {{ $partido->nombre }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay partidos registrados</option>
                                @endforelse
                            </select>
                            <div class="form-text">Partidos registrados en el módulo <a href="{{ route('partidos.index') }}">Partidos</a>.</div>
                        @endif
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        @if ( $esEdicion )
                            <label class="form-label"><strong>Persona</strong></label>
                            <input type="text" class="form-control" readonly
                                   value="{{ $voto->persona?->apellidoNombre() }} (DNI: {{ $voto->persona?->dni }})">
                            <div class="form-text">
                                Código de mesa: <strong>{{ $voto->codigoMesa() ?? '—' }}</strong>
                                (registrado en el módulo Personas).
                            </div>
                        @else
                            <label for="persona_id" class="form-label"><strong>Persona</strong></label>
                            <select class="form-select" name="persona_id" id="persona_id" required>
                                <option value="">Seleccione una persona</option>
                                @forelse ( $personas as $persona )
                                    <option value="{{ $persona->persona_id }}"
                                            @if ( old( 'persona_id', $voto->persona_id ) == $persona->persona_id ) selected @endif>
                                        {{ $persona->apellidoNombre() }} — DNI {{ $persona->dni }}{{ $persona->codigo_mesa ? ' — Mesa '.$persona->codigo_mesa : ' — sin código de mesa' }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay personas registradas</option>
                                @endforelse
                            </select>
                            <div class="form-text">Personas del módulo <a href="{{ route('personas.index') }}">Personas</a> con su código de mesa.</div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="votos" class="form-label"><strong>Votos actuales</strong></label>
                        <input type="number" class="form-control" id="votos" name="votos"
                               value="{{ old( 'votos', $voto->votos ) }}" min="0" step="1" required>
                        <div class="form-text">Cantidad de votos registrada actualmente.</div>
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
                    <p class="small text-muted mb-1">Registrado: {{ $voto->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="small text-muted mb-3">Última edición: {{ $voto->updated_at?->format('d/m/Y H:i') }}</p>
                @else
                    <p class="small text-muted mb-3">
                        Seleccione el partido y la persona, y escriba la cantidad de votos actuales.
                        Un partido solo tiene un registro de votos por persona.
                    </p>
                @endif
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('votos.index') }}">
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
