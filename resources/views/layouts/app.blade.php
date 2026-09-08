<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistema para Bases')</title>

    <!-- Desktop favicons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16">

    <!-- Styles -->
    {{-- Template SB Admin v7 (incluye Bootstrap 5 compilado) --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('css/styles.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/all.min.css') }}">
    <!-- DataTables (jQuery) integración Bootstrap 5 -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/buttons.bootstrap5.min.css') }}">

    @stack('styles')
</head>

<body class="sb-nav-fixed">
    <!-- Top navigation -->
    @include('layouts.navigation')

    <div id="layoutSidenav">
        {{-- Barra lateral (sidebar) --}}
        @include('layouts.sidenav')

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
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

    <!-- DataTables -->
    <script type="text/javascript" src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Exportación de DataTables (Excel, PDF, CSV, Copiar, Imprimir) -->
    <script type="text/javascript" src="{{ asset('js/jszip.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/pdfmake.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/vfs_fonts.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/dataTables.buttons.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/buttons.bootstrap5.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/buttons.html5.min.js') }}"></script>

    <!-- Template JS -->
    <script type="text/javascript" src="{{ asset('js/scripts.js') }}"></script>

    <!-- Corregir botones de exportación de DataTables -->
    <script>
        $.extend(true, $.fn.dataTable.Buttons.defaults.dom, {
            button: { className: 'btn btn-outline-secondary btn-sm' },
        });
    </script>

    @stack('scripts')
    @yield('footer')
</body>

</html>