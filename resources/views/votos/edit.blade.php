@extends('layouts.app')

@section('title', 'Actualizar Votos')

@section('content')

    <h1 class="mt-4 h3">Gestión de votos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('votos.index') }}">Gestión de votos</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'votos.update', [ 'voto' => $voto ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'votos.form', [ 'titulo' => 'Actualizar Votos', 'esEdicion' => true ] )

    </form>

@endsection
