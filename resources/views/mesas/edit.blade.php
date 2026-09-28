@extends('layouts.app')

@section('title', 'Actualizar Mesa de Votación')

@section('content')

    <h1 class="mt-4 h3">Mesas de Votación</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('mesas.index') }}">Mesas de votación</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'mesas.update', [ 'mesa' => $mesa ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'mesas.form', [ 'titulo' => 'Actualizar Mesa de Votación' ] )

    </form>

@endsection