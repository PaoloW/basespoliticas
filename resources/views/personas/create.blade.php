@extends('layouts.app')

@section('title', 'Registrar Persona')

@section('content')

    <h1 class="mt-4 h3">Registrar Persona</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ $rutaOrigen }}">{{ $etiquetaOrigen }}</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'personas.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'personas.form', [ 'titulo' => 'Registrar Persona' ] )

    </form>

@endsection