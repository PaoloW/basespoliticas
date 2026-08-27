@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card shadow-lg border-0 rounded-lg mt-5">
            <div class="card-header">
                <h3 class="text-center font-weight-light my-4">
                    <i class="fas fa-hand-fist me-2"></i>Sistemas para Bases
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    @if ( $errors->any() )
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="dni" name="dni"
                               placeholder="DNI del usuario" value="{{ old('dni') }}"
                               maxlength="8" autocomplete="username" required autofocus>
                        <label for="dni">DNI</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control password-field" id="clave" name="clave"
                               value="" placeholder="Contraseña" spellcheck="false" autocomplete="current-password" required>
                        <label for="clave">Contraseña</label>
                        <button type="button" class="btn btn-outline-secondary btn-toggle-password"
                                data-target="clave" title="Mostrar/ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-end mt-4 mb-0">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-1"></i>Ingresar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
    <style>
        .form-floating { position: relative; }
        .form-floating .btn-toggle-password {
            position: absolute; top: 6px; right: 6px; z-index: 5;
        }
    </style>
@endsection