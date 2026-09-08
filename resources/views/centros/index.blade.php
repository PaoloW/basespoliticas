@extends('layouts.app')

@section('title', 'Gestión de Centros de Votación')

@section('content')
<h1 class="mt-4 h3">Centros de Votación</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="#">Gestión</a></li>
    <li class="breadcrumb-item active">Centros de votación</li>
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
        <div><i class="fas fa-school me-2"></i>Listado de centros de votación</div>
        <a class="btn btn-sm btn-primary" href="{{ route('centros.create') }}">
            <i class="fas fa-plus me-1"></i>Nuevo
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover w-100" id="tabla-centros">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descripción</th>
                        <th>Ubicación</th>
                        <th>Distrito</th>
                        <th class="text-center">Mesas</th>
                        <th class="text-center">Votantes</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ( $centros as $centro )
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $centro->descripcion }}</td>
                        <td>{{ $centro->ubicacion ?? '—' }}</td>
                        <td>{{ $centro->distrito ?? '—' }}</td>
                        <td class="text-center">{{ $centro->totalMesas() }}</td>
                        <td class="text-center">{{ $centro->totalVotantes() }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Editar"
                               href="{{ route('centros.edit', $centro) }}"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('centros.destroy', $centro) }}" method="POST" class="d-inline"
                                  data-confirm="¿Realmente desea eliminar este centro de votación?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    {{-- Sin registros: DataTables mostrará el mensaje ("Ningún dato disponible en esta tabla") --}}
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
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
            });

            $('#tabla-centros').DataTable({
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