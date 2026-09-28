@extends('layouts.app')

@section('title', 'Registrar Mesa de Votación')

@section('content')

    <h1 class="mt-4 h3">Mesas de Votación</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('mesas.index') }}">Mesas de votación</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'mesas.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'mesas.form', [ 'titulo' => 'Registrar Mesa de Votación' ] )

    </form>

@endsection