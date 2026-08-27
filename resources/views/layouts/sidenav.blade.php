<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <div class="sb-sidenav-menu-heading">Principal</div>
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Inicio
                </a>

                @auth
                    <div class="sb-sidenav-menu-heading">Gestión</div>
                    @if ( auth()->user()->esAdmin() )
                        <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Usuarios
                        </a>
                    @endif
                @endauth

                <div class="sb-sidenav-menu-heading">Cuenta</div>
                @auth
                    <a class="nav-link" href="{{ route('usuarios.edit', auth()->id()) }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-cog"></i></div>
                        Cambiar datos
                    </a>
                @else
                    <a class="nav-link" href="{{ route('login') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-sign-in-alt"></i></div>
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Conectado como:</div>
            {{ auth()->user()->nombre ?? 'Invitado' }}
        </div>
    </nav>
</div>