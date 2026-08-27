/*!
 * Scripts propios de la aplicación (Sistema para Bases)
 * Basado en el template SB Admin v7 de Start Bootstrap.
 * Licencia MIT.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Alternar la barra lateral (sidenav)
    var sidebarToggle = document.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (event) {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem(
                'sb|sidebar-toggle',
                document.body.classList.contains('sb-sidenav-toggled')
            );
        });
        // Persistir el estado del sidebar entre recargas
        if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
            document.body.classList.add('sb-sidenav-toggled');
        }
    }

    // Inicializar tooltips de Bootstrap 5
    var tooltipList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipList.forEach(function (element) {
        new bootstrap.Tooltip(element);
    });

    // Mostrar/ocultar contraseña al pulsar el botón con clase .btn-toggle-password
    [].slice.call(document.querySelectorAll('.btn-toggle-password')).forEach(function (boton) {
        var objetivo = document.getElementById(boton.getAttribute('data-target'));
        if (!objetivo) return;
        boton.addEventListener('click', function (event) {
            event.preventDefault();
            var mostrando = objetivo.getAttribute('type') === 'text';
            objetivo.setAttribute('type', mostrando ? 'password' : 'text');
            var icono = boton.querySelector('i');
            if (icono) {
                icono.classList.toggle('fa-eye');
                icono.classList.toggle('fa-eye-slash');
            }
        });
    });
});