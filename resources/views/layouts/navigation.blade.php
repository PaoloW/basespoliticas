<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Marca -->
    <a class="navbar-brand ps-3" href="{{ route('home') }}">
        <i class="fas fa-hand-fist me-2"></i>Sistema para Bases
    </a>

    <!-- Toggle de la barra lateral -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"
            href="#!" aria-label="Alternar menú"><i class="fas fa-bars"></i></button>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        @auth
            <li class="nav-item me-1">
                {{-- Alternar entre modo claro y modo oscuro (se guarda en el usuario) --}}
                <button type="button" class="nav-link btn btn-link" id="btnTema"
                        data-bs-toggle="tooltip" title="Cambiar a modo oscuro">
                    <i class="fas fa-moon fa-fw" id="iconoTema"></i>
                </button>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                    <span class="ms-1 d-none d-sm-inline">{{ auth()->user()->nombre }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="{{ route('usuarios.edit', auth()->id()) }}"><i class="fas fa-user-cog me-1"></i>Cambiar datos</a></li>
                    <li><a class="dropdown-item" href="{{ route('home') }}"><i class="fas fa-tachometer-alt me-1"></i>Inicio</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-1"></i>Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </li>
        @else
            <li class="nav-item">
                <a class="nav-link" href="{{ route('login') }}"><i class="fas fa-sign-in-alt me-1"></i>Iniciar sesión</a>
            </li>
        @endauth
    </ul>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnTema = document.getElementById('btnTema');
        if (!btnTema) {
            return;
        }

        const etiquetaHtml = document.documentElement;
        const iconoTema = document.getElementById('iconoTema');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Aplica el tema en la interfaz y actualiza icono y tooltip.
        const aplicarTema = function (tema) {
            etiquetaHtml.setAttribute('data-bs-theme', tema);
            iconoTema.className = (tema === 'dark' ? 'fas fa-sun' : 'fas fa-moon') + ' fa-fw';
            const texto = tema === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
            btnTema.setAttribute('title', texto);
            btnTema.setAttribute('data-bs-original-title', texto);
        };

        aplicarTema(etiquetaHtml.getAttribute('data-bs-theme') || 'light');

        btnTema.addEventListener('click', function () {
            const tema = etiquetaHtml.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            aplicarTema(tema);

            // Persiste la preferencia en la tabla de usuarios.
            fetch('{{ route('usuarios.tema') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ tema: tema }),
            }).catch(function (error) {
                console.error(error);
            });
        });
    });
</script>