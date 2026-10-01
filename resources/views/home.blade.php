@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<h1 class="mt-4 h3">Inicio</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">Panel de control</li>
</ol>

@if ( session('success') )
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-primary mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Bienvenido</div>
                    <div class="fw-bold">{{ auth()->user()->nombre }}</div>
                </div>
                <i class="fas fa-user fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-success mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Rol de la cuenta</div>
                    <div class="fw-bold">
                        @if ( auth()->user()->esAdmin() ) Administrador del sistema @else Usuario @endif
                    </div>
                </div>
                <i class="fas fa-user-tag fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card text-white bg-dark mb-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small opacity-75">Conexión</div>
                    <div class="fw-bold">Sesión activa</div>
                </div>
                <i class="fas fa-shield-alt fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-poll me-2"></i>Mesas con votos registrados</span>
                <span class="badge bg-primary">{{ $mesasConVotos }} / {{ $mesasTotales }} mesas</span>
            </div>
            <div class="card-body">
                <div style="height: 180px;">
                    <canvas id="graficoMesas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-column me-2"></i>Votos por partido
            </div>
            <div class="card-body">
                <div style="height: 320px;">
                    <canvas id="graficoPartidos"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-home me-2"></i>Bienvenida al sistema
    </div>
    <div class="card-body">
        <p class="mb-0">
            Esta es su vista principal. Seleccione una opción del menú lateral para gestionar la
            información del sistema Sistema para Bases (afiliados, mesas, personeros y centros).
        </p>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Chart.js en local (descargado en public/js) --}}
    <script type="text/javascript" src="{{ asset('js/chart.umd.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/chartjs-plugin-datalabels.min.js') }}"></script>
    <script type="text/javascript">
        // Registro explícito del plugin (v2.2.0 no se auto-registra).
        Chart.register(ChartDataLabels);

        document.addEventListener('DOMContentLoaded', function () {
            const datosMesas = {
                conVotos: {{ (int) $mesasConVotos }},
                total: {{ (int) $mesasTotales }},
            };
            const partidos_total = @json($partidos);
            // Mantener solo los 10 partidos con mayor cantidad de votos.
            const partidos = partidos_total.sort((a, b) => b.votos - a.votos).slice(0, 10);
            
            // Colores según el tema activo: se recalculan al cambiar de tema.
            let colorTexto = '#212529';
            let colorRejilla = 'rgba(0, 0, 0, 0.1)';

            function actualizarColoresTema() {
                const esOscuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                colorTexto = esOscuro ? '#ffffff' : '#212529';
                colorRejilla = esOscuro ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            }
            actualizarColoresTema();

            // Instancias de los gráficos (para actualizarlos al cambiar el tema).
            let chartMesas = null;
            let chartPartidos = null;
            const porcentaje = datosMesas.total > 0
                ? Math.round((datosMesas.conVotos * 100) / datosMesas.total)
                : 0;

            // Logos de los partidos: se cargan antes de dibujar el gráfico.
            const logos = partidos.map(function (partido) {
                if (!partido.logo) {
                    return null;
                }
                const imagen = new Image();
                imagen.src = partido.logo;
                return imagen;
            });

            const esperarLogos = Promise.all(logos.map(function (imagen) {
                if (!imagen || imagen.complete) {
                    return Promise.resolve();
                }
                return new Promise(function (resolver) {
                    imagen.onload = resolver;
                    imagen.onerror = resolver;
                });
            }));

            esperarLogos.then(function () {
                graficoMesas();
                graficoPartidos();
            });

            // Barra de progreso: mesas con votos registrados / mesas totales.
            function graficoMesas() {
                const lienzo = document.getElementById('graficoMesas');
                if (!lienzo) {
                    return;
                }

                // Porcentaje dibujado dentro (o al final) de la barra.
                const pluginPorcentaje = {
                    id: 'porcentajeMesas',
                    afterDatasetsDraw: function (chart) {
                        const barra = chart.getDatasetMeta(0).data[0];
                        if (!barra) {
                            return;
                        }
                        const ctx = chart.ctx;
                        const texto = porcentaje + '%';
                        ctx.save();
                        ctx.font = 'bold 13px sans-serif';
                        ctx.textBaseline = 'middle';
                        const anchoTexto = ctx.measureText(texto).width;
                        if (barra.width >= anchoTexto + 20) {
                            ctx.fillStyle = '#ffffff';
                            ctx.textAlign = 'right';
                            ctx.fillText(texto, barra.x - 10, barra.y);
                        } else {
                            ctx.fillStyle = colorTexto;
                            ctx.textAlign = 'left';
                            ctx.fillText(texto, barra.x + 10, barra.y);
                        }
                        ctx.restore();
                    },
                };

                chartMesas = new Chart(lienzo, {
                    type: 'bar',
                    data: {
                        labels: ['Mesas'],
                        datasets: [{
                            label: 'Mesas con votos registrados',
                            data: [porcentaje],
                            backgroundColor: '#0d6efd',
                            borderRadius: 6,
                            barThickness: 32,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { right: 40 } },
                        plugins: {
                            legend: { display: false },
                            datalabels: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function () {
                                        return ' ' + datosMesas.conVotos + ' de ' + datosMesas.total + ' mesas (' + porcentaje + '%)';
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: function (valor) { return valor + '%'; },
                                    color: colorTexto,
                                },
                                grid: { color: colorRejilla },
                            },
                            y: { display: false },
                        },
                    },
                    plugins: [pluginPorcentaje],
                });
            }

            // Barras verticales: votos por partido (logo en el eje X y color propio).
            function graficoPartidos() {
                const lienzo = document.getElementById('graficoPartidos');
                if (!lienzo) {
                    return;
                }

                // Logo de cada partido debajo de su barra, en lugar del nombre.
                const pluginLogos = {
                    id: 'logosPartidos',
                    afterDatasetsDraw: function (chart) {
                        const ctx = chart.ctx;
                        const area = chart.chartArea;
                        const ejeX = chart.scales.x;
                        const espacio = (area.right - area.left) / Math.max(partidos.length, 1);
                        ctx.save();
                        partidos.forEach(function (partido, indice) {
                            const centroX = ejeX.getPixelForTick(indice);
                            const topeY = area.bottom + 8;
                            const imagen = logos[indice];
                            if (imagen && imagen.naturalWidth) {
                                const relacion = imagen.naturalWidth / imagen.naturalHeight;
                                let ancho = Math.min(espacio - 8, 44);
                                let alto = ancho / relacion;
                                if (alto > 44) {
                                    alto = 44;
                                    ancho = alto * relacion;
                                }
                                ctx.drawImage(imagen, centroX - ancho / 2, topeY, ancho, alto);
                            } else {
                                // Sin logo: se muestra el nombre del partido.
                                ctx.fillStyle = colorTexto;
                                ctx.font = 'bold 11px sans-serif';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'top';
                                ctx.fillText(partido.nombre, centroX, topeY, Math.max(espacio - 8, 20));
                            }
                        });
                        ctx.restore();
                    },
                };

                chartPartidos = new Chart(lienzo, {
                    type: 'bar',
                    data: {
                        labels: partidos.map(function (partido) { return partido.nombre; }),
                        datasets: [{
                            label: 'Votos',
                            data: partidos.map(function (partido) { return partido.votos; }),
                            backgroundColor: partidos.map(function (partido) { return partido.color; }),
                            borderRadius: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { bottom: 56 } },
                        plugins: {
                            legend: { display: false },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                color: colorTexto,
                                font: { weight: 'bold' },
                                formatter: function (valor) { return valor; },
                            },
                            tooltip: {
                                callbacks: {
                                    title: function (items) {
                                        return items.length ? partidos[items[0].dataIndex].nombre : '';
                                    },
                                    label: function (item) {
                                        return ' ' + item.parsed.y + ' votos';
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: { display: false, autoSkip: false, maxRotation: 0 },
                                grid: { display: false },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: colorTexto, precision: 0 },
                                grid: { color: colorRejilla },
                                grace: 0.5,
                            },
                        },
                    },
                    plugins: [pluginLogos],
                });
            }

            // Adaptar los colores de labels, ejes y rejilla cuando cambia el tema.
            const observadorTema = new MutationObserver(function () {
                actualizarColoresTema();
                if (chartMesas) {
                    chartMesas.options.scales.x.ticks.color = colorTexto;
                    chartMesas.options.scales.x.grid.color = colorRejilla;
                    chartMesas.update();
                }
                if (chartPartidos) {
                    chartPartidos.options.plugins.datalabels.color = colorTexto;
                    chartPartidos.options.scales.y.ticks.color = colorTexto;
                    chartPartidos.options.scales.y.grid.color = colorRejilla;
                    chartPartidos.update();
                }
            });
            observadorTema.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme'],
            });
        });
    </script>
@endpush