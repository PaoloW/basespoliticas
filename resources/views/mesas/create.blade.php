@extends('layouts.app')

@section('title', 'Registrar Mesa de Votación')

@section('content')

    <form action="{{ route( 'mesas.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'mesas.form', [ 'titulo' => 'Registrar Mesa de Votación' ] )

    </form>

@endsection