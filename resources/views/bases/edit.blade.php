@extends('layouts.app')

@section('title', 'Actualizar Base')

@section('content')

    <form action="{{ route( 'bases.update', [ 'base' => $base ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'bases.form', [ 'titulo' => 'Actualizar Base' ] )

    </form>

@endsection