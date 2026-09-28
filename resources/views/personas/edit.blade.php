@extends('layouts.app')

@section('title', 'Actualizar Persona')

@section('content')

    <h1 class="mt-4 h3">Actualizar Persona</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('personas.index') }}">Personas</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'personas.update', [ 'persona' => $persona ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'personas.form', [ 'titulo' => 'Actualizar Persona' ] )

    </form>

@endsection