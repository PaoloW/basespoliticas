@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<h1 class="mt-4 h3">Gestión de Usuarios</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
</ol>

@if ( session('success') )
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ( $errors->any() )
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ( $errors->all() as $error )
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div><i class="fas fa-users me-2"></i>Listado de usuarios del sistema</div>
        <a class="btn btn-sm btn-primary" href="{{ route('usuarios.create') }}">
            <i class="fas fa-plus me-1"></i>Nuevo
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover w-100" id="tabla-usuarios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th class="text-center">Menús</th>
                        <th>Registrado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ( $usuarios as $usuario )
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if ( $usuario->esAdmin() )
                                <i class="fas fa-crown me-1 text-warning" data-bs-toggle="tooltip" title="Administrador"></i>
                            @endif
                            {{ $usuario->nombre }}
                        </td>
                        <td>{{ $usuario->persona?->dni }}</td>
                        <td>{{ $usuario->persona?->nombres }}</td>
                        <td>{{ $usuario->persona?->apellidos() }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $usuario->accesos_count }}</span>
                        </td>
                        <td class="text-nowrap">{{ $usuario->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar"
                               href="{{ route('usuarios.edit', $usuario) }}"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="d-inline"
                                  data-confirm="¿Realmente desea eliminar este usuario?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    {{-- Sin registros: DataTables mostrará su propio mensaje ("Ningún dato disponible en esta tabla") --}}
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('footer')
    <script>
        $(document).ready(function () {
            const botonExportar = (extend, texto, icono, clase) => ({
                extend: extend,
                text: '<i class="' + icono + ' me-1"></i>' + texto,
                className: 'btn btn-sm ' + clase,
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
            });

            $('#tabla-usuarios').DataTable({
                language: { url: "{{ asset('datatables/spanish.json') }}" },
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt' +
                     '<"row mt-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"p>>',
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                buttons: [
                    botonExportar('copy', 'Copiar', 'fas fa-copy', 'btn-outline-secondary'),
                    botonExportar('excel', 'Excel', 'fas fa-file-excel', 'btn-outline-success'),
                    botonExportar('pdf', 'PDF', 'fas fa-file-pdf', 'btn-outline-danger'),
                ],
            });
        });
    </script>

    <script>
        // Confirmación para formularios con atributo data-confirm
        document.addEventListener('DOMContentLoaded', function () {
            [].slice.call(document.querySelectorAll('form[data-confirm]')).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm(form.getAttribute('data-confirm'))) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endsection