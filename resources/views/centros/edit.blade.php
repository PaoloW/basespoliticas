@extends('layouts.app')

@section('title', 'Actualizar Centro de Votación')

@section('content')

    <h1 class="mt-4 h3">Centros de Votación</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('centros.index') }}">Centros de votación</a></li>
        <li class="breadcrumb-item active" aria-current="page">Actualizar</li>
    </ol>

    <form action="{{ route( 'centros.update', [ 'centro' => $centro ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'centros.form', [ 'titulo' => 'Actualizar Centro de Votación' ] )

    </form>

@endsection