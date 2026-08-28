@extends('layouts.app')

@section('title', 'Registrar Persona')

@section('content')

    <form action="{{ route( 'personas.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'personas.form', [ 'titulo' => 'Registrar Persona' ] )

    </form>

@endsection