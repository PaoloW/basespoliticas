@extends('layouts.app')

@section('title', 'Actualizar Usuario')

@section('content')

    <form action="{{ route( 'usuarios.update', [ 'usuario' => $usuario ] ) }}" method="POST" enctype="multipart/form-data">
        
        @method('PUT')

        @include( 'usuarios.form', [ 'titulo' => 'Actualizar Usuario' ] )

    </form>

@endsection