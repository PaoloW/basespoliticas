@extends('layouts.app')

@section('title', 'Registrar Cargo')

@section('content')

    <form action="{{ route( 'cargos.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'cargos.form', [ 'titulo' => 'Registrar Cargo' ] )

    </form>

@endsection