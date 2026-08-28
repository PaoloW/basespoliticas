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
                    <a class="nav-link {{ request()->routeIs('personas.*') ? 'active' : '' }}" href="{{ route('personas.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                        Personas
                    </a>
                    <a class="nav-link {{ request()->routeIs('centros.*') ? 'active' : '' }}" href="{{ route('centros.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-school"></i></div>
                        Centros de votación
                    </a>
                    <a class="nav-link {{ request()->routeIs('mesas.*') ? 'active' : '' }}" href="{{ route('mesas.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Mesas de votación
                    </a>
                    <a class="nav-link {{ request()->routeIs('cargos.*') ? 'active' : '' }}" href="{{ route('cargos.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-briefcase"></i></div>
                        Cargos
                    </a>
                    <a class="nav-link {{ request()->routeIs('bases.*') ? 'active' : '' }}" href="{{ route('bases.index') }}">
                        <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                        Bases
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