@extends('layouts.app')

@section('title', 'Registrar Cargo')

@section('content')

    <h1 class="mt-4 h3">Cargos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cargos.index') }}">Cargos</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'cargos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'cargos.form', [ 'titulo' => 'Registrar Cargo' ] )

    </form>

@endsection