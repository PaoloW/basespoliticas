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

@php
    $esEdicion = $esEdicion ?? false;
@endphp

<div class="row">
    <div class="col-lg-8 col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-check me-2"></i><strong>Datos del personero</strong>
                <p class="small text-muted my-1"><em>Los campos resaltados son obligatorios</em></p>
            </div>
            <div class="card-body">
                {{-- Persona: selector compartido (búsqueda por DNI, listado y alta de persona nueva) --}}
                @include('partials.selector-persona', [
                    'spOrigen' => 'personeros',
                    'spUrlBuscar' => route('personeros.buscarPersona'),
                    'spUrlModal' => route('personeros.personas'),
                    'spPersona' => $personaSeleccionada,
                    'spBloquear' => $esEdicion,
                    'spEtiqueta' => 'DNI del personero',
                    'spEtiquetaNombre' => 'Nombres',
                ])

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

@section('footer')
    <script>
        $(document).ready(function () {
            const $form = $('#form-personero');
            if (!$form.length) {
                return;
            }

            $form.on('submit', function () {
                // La persona se elige con el selector compartido (hidden persona_id).
                if (!$('#persona_id').val()) {
                    $('#selector-persona-resultado').html('<p class="alert alert-danger py-2 mb-0">' +
                        'Debe buscar y seleccionar la persona antes de guardar.</p>');
                    $('#selector-persona-dni').focus();
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
