@extends('layouts.app')

@section('title', 'Actualizar Votos')

@section('content')

    <form action="{{ route( 'votos.update', [ 'voto' => $voto ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'votos.form', [ 'titulo' => 'Actualizar Votos', 'esEdicion' => true ] )

    </form>

@endsection
