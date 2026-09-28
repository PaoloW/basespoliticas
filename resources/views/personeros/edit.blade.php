@extends('layouts.app')

@section('title', 'Editar Personero')

@section('content')
    <h1 class="mt-4 h3">Personeros</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('personeros.index') }}">Personeros</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>

    <form action="{{ route('personeros.update', ['personero' => $personero]) }}" method="POST" id="form-personero">

        @method('PUT')

        @include('personeros.form', ['esEdicion' => true])

    </form>
@endsection