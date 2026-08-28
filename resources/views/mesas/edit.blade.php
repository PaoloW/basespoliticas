@extends('layouts.app')

@section('title', 'Actualizar Mesa de Votación')

@section('content')

    <form action="{{ route( 'mesas.update', [ 'mesa' => $mesa ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'mesas.form', [ 'titulo' => 'Actualizar Mesa de Votación' ] )

    </form>

@endsection