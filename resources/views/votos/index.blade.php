@extends('layouts.app')

@section('title', 'Ver Conteo de Votos')

@section('content')
<h1 class="mt-4 h3">Conteo de votos</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Ver conteo de votos</li>
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

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Mesas con conteo</div>
                    <div class="fw-bold fs-4">{{ $mesas->count() }}</div>
                </div>
                <i class="fas fa-user-check fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Total de votos</div>
                    <div class="fw-bold fs-4">{{ number_format($totalVotos) }}</div>
                </div>
                <i class="fas fa-vote-yea fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-dark h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Partidos con conteo</div>
                    <div class="fw-bold fs-4">{{ $totalPartidos }}</div>
                </div>
                <i class="fas fa-flag fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div><i class="fas fa-chart-line me-2"></i>Conteos registrados por mesa</div>
        <a class="btn btn-sm btn-primary" href="{{ route('votos.registrar') }}">
            <i class="fas fa-plus me-1"></i>Registrar conteo
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover w-100" id="tabla-conteos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mesa</th>
                        <th>Centro de votación</th>
                        <th>Personero</th>
                        <th>DNI</th>
                        <th class="text-center">Partidos</th>
                        <th class="text-center">Total de votos</th>
                        <th class="text-center">Último conteo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ( $mesas as $mesa )
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $mesa->etiqueta() }}</td>
                        <td>{{ $mesa->centro?->descripcion ?? '—' }}</td>
                        <td>{{ $mesa->personero?->persona?->apellidoNombre() ?? '— Sin personero —' }}</td>
                        <td>{{ $mesa->personero?->persona?->dni ?? '—' }}</td>
                        <td class="text-center">{{ $mesa->votos_count }}</td>
                        <td class="text-center fw-bold">{{ $mesa->total_votos ?? 0 }}</td>
                        <td class="text-center text-nowrap">
                            {{ $mesa->ultimo_conteo ? \Illuminate\Support\Carbon::parse($mesa->ultimo_conteo)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="text-end text-nowrap">
                            {{-- Edición: la mesa queda bloqueada, solo se añade personero (si falta) y se editan votos. --}}
                            <a class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Revisar / editar conteo"
                               href="{{ route('votos.edit', ['voto' => $mesa->voto_id]) }}"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    @empty
                    {{-- Sin registros: DataTables mostrará su propio mensaje --}}
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
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
            });

            $('#tabla-conteos').DataTable({
                language: { url: "{{ asset('datatables/spanish.json') }}" },
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt' +
                     '<"row mt-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"p>>',
                order: [[6, 'desc']],
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
@endsection