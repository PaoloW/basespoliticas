@extends('layouts.app')

@section('title', 'Registrar Votos')

@section('content')

    <form action="{{ route( 'votos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'votos.form', [ 'titulo' => 'Registrar Votos', 'esEdicion' => false ] )

    </form>

@endsection
