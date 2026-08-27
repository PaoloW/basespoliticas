@extends('layouts.app')

@section('title', 'Registrar Usuario')

@section('content')

    <form action="{{ route( 'usuarios.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'usuarios.form', [ 'titulo' => 'Registrar Usuario' ] )

    </form>

@endsection