@extends('layouts.app')

@section('title', 'Registrar Partido')

@section('content')

    <form action="{{ route( 'partidos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'partidos.form', [ 'titulo' => 'Registrar Partido' ] )

    </form>

@endsection
