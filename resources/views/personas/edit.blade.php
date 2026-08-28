@extends('layouts.app')

@section('title', 'Actualizar Persona')

@section('content')

    <form action="{{ route( 'personas.update', [ 'persona' => $persona ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'personas.form', [ 'titulo' => 'Actualizar Persona' ] )

    </form>

@endsection