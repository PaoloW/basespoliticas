<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistema para Bases')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16">

    <link rel="stylesheet" type="text/css" href="{{ asset('css/styles.css') }}">
    {{-- Font Awesome --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('css/all.min.css') }}">

    @stack('styles')
</head>

<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    @yield('content')
                </div>
            </main>
        </div>

        <div id="layoutAuthentication_footer">
            <footer class="py-4 mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Sistema para Bases {{ date('Y') }}</div>
                        <div class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>Sistema de gestión interna
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Core jQuery -->
    <script type="text/javascript" src="{{ asset('js/jquery-3.5.1.min.js') }}"></script>
    <!-- Bootstrap core JS -->
    <script type="text/javascript" src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <!-- Template JS -->
    <script type="text/javascript" src="{{ asset('js/scripts.js') }}"></script>

    @stack('scripts')
    @yield('footer')
</body>

</html>