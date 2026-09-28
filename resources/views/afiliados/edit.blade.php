@extends('layouts.app')

@section('title', 'Editar Afiliación')

@section('content')
<h1 class="mt-4 h3">Editar afiliación</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="#">Procesos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('afiliados.index') }}">Afiliados</a></li>
    <li class="breadcrumb-item active">Editar</li>
</ol>

    <form action="{{ route( 'afiliados.update', [ 'afiliado' => $afiliado ] ) }}" method="POST" id="form-afiliado">

        @method('PUT')

        @include( 'afiliados.form', [ 'titulo' => 'Editar Afiliación', 'esEdicion' => true ] )

    </form>

@endsection
