@extends('layouts.app')

@section('title', 'Registrar Conteo de Votos')

@section('content')

    <h1 class="mt-4 h3">Registrar conteo de votos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Registrar conteo de votos</li>
    </ol>

    <form action="{{ route('votos.guardar') }}" method="POST">

        @include('votos.form', ['esEdicion' => false])

    </form>

@endsection