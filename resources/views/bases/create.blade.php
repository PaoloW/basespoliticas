@extends('layouts.app')

@section('title', 'Registrar Base')

@section('content')

    <form action="{{ route( 'bases.store' ) }}" method="POST" enctype="multipart/form-data">

        @include( 'bases.form', [ 'titulo' => 'Registrar Base' ] )

    </form>

@endsection