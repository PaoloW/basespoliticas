@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<h1 class="mt-4 h3">Inicio</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">Panel de control</li>
</ol>

@if ( session('success') )
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-primary mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Bienvenido</div>
                    <div class="fw-bold">{{ auth()->user()->nombre }}</div>
                </div>
                <i class="fas fa-user fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-success mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Rol de la cuenta</div>
                    <div class="fw-bold">
                        @if ( auth()->user()->esAdmin() ) Administrador del sistema @else Usuario @endif
                    </div>
                </div>
                <i class="fas fa-user-tag fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-dark mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Conexión</div>
                    <div class="fw-bold">Sesión activa</div>
                </div>
                <i class="fas fa-shield-alt fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-home me-2"></i>Bienvenida al sistema
    </div>
    <div class="card-body">
        <p class="mb-0">
            Esta es su vista principal. Seleccione una opción del menú lateral para gestionar la
            información del sistema Sistema para Bases (afiliados, mesas, personeros y centros).
        </p>
    </div>
</div>
@endsection