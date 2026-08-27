<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistema')</title>

    <!-- Desktop favicons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" sizes="16x16">

    <!-- Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pdf.styles.css') }}">

</head>

<body>
    @yield('content')
</body>

</html>
