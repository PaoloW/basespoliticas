@extends('layouts.app')

@section('title', 'Registrar Centro de Votación')

@section('content')

    <form action="{{ route( 'centros.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'centros.form', [ 'titulo' => 'Registrar Centro de Votación' ] )

    </form>

@endsection