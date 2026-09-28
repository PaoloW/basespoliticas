@extends('layouts.app')

@section('title', 'Registrar Votos')

@section('content')

    <h1 class="mt-4 h3">Gestión de votos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('votos.index') }}">Gestión de votos</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'votos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'votos.form', [ 'titulo' => 'Registrar Votos', 'esEdicion' => false ] )

    </form>

@endsection
