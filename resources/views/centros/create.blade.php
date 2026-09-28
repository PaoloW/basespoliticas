@extends('layouts.app')

@section('title', 'Registrar Centro de Votación')

@section('content')

    <h1 class="mt-4 h3">Centros de Votación</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('centros.index') }}">Centros de votación</a></li>
        <li class="breadcrumb-item active" aria-current="page">Registrar</li>
    </ol>

    <form action="{{ route( 'centros.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'centros.form', [ 'titulo' => 'Registrar Centro de Votación' ] )

    </form>

@endsection