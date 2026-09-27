@extends('layouts.app')

@section('title', 'Actualizar Partido')

@section('content')

    <form action="{{ route( 'partidos.update', [ 'partido' => $partido ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'partidos.form', [ 'titulo' => 'Actualizar Partido' ] )

    </form>

@endsection
