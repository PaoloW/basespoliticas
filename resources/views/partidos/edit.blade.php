@extends('layouts.app')

@section('title', 'Actualizar Partido')

@section('content')

    <h1 class="mt-4 h3">Partidos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('partidos.index') }}">Partidos</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'partidos.update', [ 'partido' => $partido ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'partidos.form', [ 'titulo' => 'Actualizar Partido' ] )

    </form>

@endsection
