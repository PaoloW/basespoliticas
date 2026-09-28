@extends('layouts.app')

@section('title', 'Registrar Personero')

@section('content')
    <h1 class="mt-4 h3">Personeros</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('personeros.index') }}">Personeros</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route('personeros.store') }}" method="POST" id="form-personero">

        @include('personeros.form', ['esEdicion' => false])

    </form>
@endsection