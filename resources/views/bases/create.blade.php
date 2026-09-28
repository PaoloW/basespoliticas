@extends('layouts.app')

@section('title', 'Registrar Base')

@section('content')

    <h1 class="mt-4 h3">Bases</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('bases.index') }}">Bases</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>

    <form action="{{ route( 'bases.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'bases.form', [ 'titulo' => 'Registrar Base' ] )

    </form>

@endsection