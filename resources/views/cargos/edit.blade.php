@extends('layouts.app')

@section('title', 'Actualizar Cargo')

@section('content')

    <h1 class="mt-4 h3">Cargos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cargos.index') }}">Cargos</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'cargos.update', [ 'cargo' => $cargo ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'cargos.form', [ 'titulo' => 'Actualizar Cargo' ] )

    </form>

@endsection