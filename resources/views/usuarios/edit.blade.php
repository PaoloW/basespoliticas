@extends('layouts.app')

@section('title', 'Actualizar Usuario')

@section('content')

    <h1 class="mt-4 h3">Actualizar Usuario</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'usuarios.update', [ 'usuario' => $usuario ] ) }}" method="POST" enctype="multipart/form-data">
        
        @method('PUT')

        @include( 'usuarios.form', [ 'titulo' => 'Actualizar Usuario' ] )

    </form>

@endsection