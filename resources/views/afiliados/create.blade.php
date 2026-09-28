@extends('layouts.app')

@section('title', 'Registrar Afiliación')

@section('content')
<h1 class="mt-4 h3">Registrar afiliación</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('afiliados.index') }}">Afiliados</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
</ol>

    <form action="{{ route( 'afiliados.store' ) }}" method="POST" id="form-afiliado">

        @include( 'afiliados.form', [ 'titulo' => 'Registrar Afiliación', 'esEdicion' => false ] )

    </form>

@endsection
