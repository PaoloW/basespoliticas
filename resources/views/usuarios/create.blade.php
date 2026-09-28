@extends('layouts.app')

@section('title', 'Registrar Usuario')

@section('content')

    <h1 class="mt-4 h3">Registrar Usuario</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Nuevo</li>
    </ol>

    <form action="{{ route( 'usuarios.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'usuarios.form', [ 'titulo' => 'Registrar Usuario' ] )

    </form>

@endsection