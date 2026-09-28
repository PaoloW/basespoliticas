@extends('layouts.app')

@section('title', 'Actualizar Base')

@section('content')

    <h1 class="mt-4 h3">Bases</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('bases.index') }}">Bases</a></li>
        <li class="breadcrumb-item active">Actualizar</li>
    </ol>

    <form action="{{ route( 'bases.update', [ 'base' => $base ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'bases.form', [ 'titulo' => 'Actualizar Base' ] )

    </form>

@endsection