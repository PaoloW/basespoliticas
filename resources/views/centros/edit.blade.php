@extends('layouts.app')

@section('title', 'Actualizar Centro de Votación')

@section('content')

    <form action="{{ route( 'centros.update', [ 'centro' => $centro ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'centros.form', [ 'titulo' => 'Actualizar Centro de Votación' ] )

    </form>

@endsection