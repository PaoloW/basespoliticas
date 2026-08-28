@extends('layouts.app')

@section('title', 'Actualizar Cargo')

@section('content')

    <form action="{{ route( 'cargos.update', [ 'cargo' => $cargo ] ) }}" method="POST" enctype="multipart/form-data">

        @method('PUT')

        @include( 'cargos.form', [ 'titulo' => 'Actualizar Cargo' ] )

    </form>

@endsection