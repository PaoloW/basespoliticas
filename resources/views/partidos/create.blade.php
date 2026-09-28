@extends('layouts.app')

@section('title', 'Registrar Partido')

@section('content')

    <h1 class="mt-4 h3">Partidos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('partidos.index') }}">Partidos</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'partidos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'partidos.form', [ 'titulo' => 'Registrar Partido' ] )

    </form>

@endsection
