@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="fs-3 mb-1">Buscador 1</h1>
            <p>Escriba el nombre o la dirección del cliente o propiedad en el buscador para acceder a su información.</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 position-relative">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar cliente o propiedad..." autocomplete="off">
            <div id="autocomplete-list" class="list-group position-absolute" style="z-index:1000; width: 100%;"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fs-3 mb-1">Pendientes</h1>
                    <p>Tienes <b id="contador-pendientes">0</b> propiedades con transferencias pendientes</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" id="tabla-pendientes-container">
                <div id="empty-state" class="text-center py-5 px-3" style="display: none;">
                    <div class="celebration-icon mb-3">
                        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h3 class="text-success mb-2 fs-4 fs-md-3">¡Excelente trabajo!</h3>
                    <p class="text-muted mb-0 text-wrap">Todas tus propiedades están al día.<br>No tienes transferencias pendientes por registrar.</p>
                </div>
                <div class="table-responsive" id="tabla-wrapper">
                    <table class="table mb-0 text-nowrap table-hover table-card-mobile" id="tabla-pendientes">
                    <thead class="table-light border-light">
                        <tr>
                            <th><b>Dirección</b></th>
                            <th data-col="arrendador"><b>Cobros al Arrendador</b></th>
                            <th data-col="arrendatario"><b>Cobros al Arrendatario</b></th>
                            <th data-col="corredor"><b>Cobros al Corredor</b></th>
                        </tr>
                    </thead>
                    <tbody id="body-pendientes">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="border-bottom-0">
                                Mostrando <b id="info-desde">0</b>–<b id="info-hasta">0</b> de <b id="info-total">0</b> propiedades
                            </td>
                            <td colspan="3" class="border-bottom-0">
                                <nav aria-label="Page navigation" class="d-flex justify-content-end">
                                    <ul class="pagination mb-0" id="paginacion">
                                    </ul>
                                </nav>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalCobro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body-cobro"></div>
            <div class="modal-footer">
                <button id="btn-detallar" class="btn btn-secondary">Detallar pago</button>
                <button id="btn-registrar" class="btn btn-primary">Registrar pago</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #empty-state {
        animation: fadeInUp 0.6s ease-out;
        overflow: hidden;
    }

    .celebration-icon {
        animation: bounceIn 0.8s ease-out, pulse 2s infinite 1s;
        overflow: hidden;
        display: inline-block;
        padding: 10px;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            transform: scale(1);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    .empty-state-icon {
        width: 80px;
        height: 80px;
    }

    @media (max-width: 576px) {
        .empty-state-icon {
            width: 60px;
            height: 60px;
        }
    }

    #empty-state {
        max-width: 100%;
        overflow-wrap: break-word;
    }
</style>
@endpush

@push('scripts')
<script>
    const POR_PAGINA = 5;
    let paginaActual = 1;

    function renderCobros(lista = []) {
        if (!lista.length) return '<span class="text-muted">—</span>';

        return lista.map(c => {
            let color = "secondary";

            if (c.estado === "Pendiente") color = "warning";
            if (c.estado === "Vencido") color = "danger";
            if (c.estado === "Incompleto") color = "info";

            return `
                <div class="mb-1">
                    <button
                        class="btn btn-sm btn-${color} w-100 text-start btn-cobro"
                        data-cobro='${JSON.stringify(c)}'
                    >
                        ${c.concepto}
                    </button>
                </div>
            `;
        }).join('');
    }

    async function cargarPendientes(pagina = 1) {
        try {
            const tbody = document.getElementById('body-pendientes');
            if (typeof window.showElLoading === 'function') {
                window.showElLoading(tbody, 4);
            }

            const res = await fetch(`/api/dashboard/pendientes?pagina=${pagina}&por_pagina=${POR_PAGINA}`);
            const json = await res.json();

            if (json.error) {
                console.error(json.error);
                if (typeof window.hideElLoading === 'function') {
                    window.hideElLoading(tbody);
                }
                return;
            }

            const contador = document.getElementById('contador-pendientes');

            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(tbody);
            }

            tbody.innerHTML = '';

            const tablaWrapper = document.getElementById('tabla-wrapper');
            const tabla = document.getElementById('tabla-pendientes');
            const emptyState = document.getElementById('empty-state');
            const tfoot = tabla.querySelector('tfoot');

            const totalProps = json.total ?? json.data.length;

            if (totalProps === 0) {
                if (tablaWrapper) tablaWrapper.style.display = 'none';
                emptyState.style.display = 'block';
            } else {
                if (tablaWrapper) tablaWrapper.style.display = 'block';
                emptyState.style.display = 'none';
            }

            // Detect which columns have cobros on this page
            const hayCol = {
                arrendador: json.data.some(item => item.arrendador && item.arrendador.length > 0),
                arrendatario: json.data.some(item => item.arrendatario && item.arrendatario.length > 0),
                corredor: json.data.some(item => item.corredor && item.corredor.length > 0)
            };

            // Show/hide column headers
            document.querySelectorAll('th[data-col]').forEach(th => {
                th.style.display = hayCol[th.dataset.col] ? '' : 'none';
            });

            json.data.forEach(item => {
                const tr = document.createElement('tr');

                let html = `<td><a href="/propiedad/ficha/${item.id}">${item.direccion}</a></td>`;

                if (hayCol.arrendador) html += `<td>${renderCobros(item.arrendador)}</td>`;
                if (hayCol.arrendatario) html += `<td>${renderCobros(item.arrendatario)}</td>`;
                if (hayCol.corredor) html += `<td>${renderCobros(item.corredor)}</td>`;

                tr.innerHTML = html;
                tbody.appendChild(tr);
            });

            // Counter
            contador.textContent = json.total ?? json.data.length;

            // Pagination info
            const total = json.total ?? json.data.length;
            const paginaResp = json.pagina ?? pagina;
            const porPagina = json.por_pagina ?? POR_PAGINA;
            const desde = total === 0 ? 0 : (paginaResp - 1) * porPagina + 1;
            const hasta = Math.min(paginaResp * porPagina, total);

            document.getElementById('info-desde').textContent = desde;
            document.getElementById('info-hasta').textContent = hasta;
            document.getElementById('info-total').textContent = total;

            // Pagination
            if (json.total_paginas) {
                renderPaginacion(paginaResp, json.total_paginas);
            }

            paginaActual = paginaResp;

        } catch (error) {
            console.error(error);
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(tbody);
            }
        }
    }

    function renderPaginacion(paginaActual, totalPaginas) {
        const ul = document.getElementById('paginacion');
        ul.innerHTML = '';

        if (totalPaginas <= 1) return;

        // Previous button
        const liPrev = document.createElement('li');
        liPrev.className = `page-item ${paginaActual === 1 ? 'disabled' : ''}`;
        liPrev.innerHTML = `<a class="page-link" href="#" aria-label="Anterior">&laquo;</a>`;
        liPrev.addEventListener('click', (e) => {
            e.preventDefault();
            if (paginaActual > 1) cargarPendientes(paginaActual - 1);
        });
        ul.appendChild(liPrev);

        // Numbered pages with sliding window
        const ventana = 2;
        let inicio = Math.max(1, paginaActual - ventana);
        let fin = Math.min(totalPaginas, paginaActual + ventana);

        if (inicio > 1) {
            agregarPagina(ul, 1, paginaActual);
            if (inicio > 2) agregarEllipsis(ul);
        }

        for (let p = inicio; p <= fin; p++) {
            agregarPagina(ul, p, paginaActual);
        }

        if (fin < totalPaginas) {
            if (fin < totalPaginas - 1) agregarEllipsis(ul);
            agregarPagina(ul, totalPaginas, paginaActual);
        }

        // Next button
        const liNext = document.createElement('li');
        liNext.className = `page-item ${paginaActual === totalPaginas ? 'disabled' : ''}`;
        liNext.innerHTML = `<a class="page-link" href="#" aria-label="Siguiente">&raquo;</a>`;
        liNext.addEventListener('click', (e) => {
            e.preventDefault();
            if (paginaActual < totalPaginas) cargarPendientes(paginaActual + 1);
        });
        ul.appendChild(liNext);
    }

    function agregarPagina(ul, numero, paginaActual) {
        const li = document.createElement('li');
        li.className = `page-item ${numero === paginaActual ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${numero}</a>`;
        li.addEventListener('click', (e) => {
            e.preventDefault();
            cargarPendientes(numero);
        });
        ul.appendChild(li);
    }

    function agregarEllipsis(ul) {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = `<span class="page-link">…</span>`;
        ul.appendChild(li);
    }

    // Initial load
    cargarPendientes(1);

    // Refactored: use shared buscador.js module (includes spinner via showElLoading/hideElLoading)
    if (typeof buscador === 'function') {
        buscador({
            input: '#searchInput',
            list: '#autocomplete-list',
            url: '/api/dashboard/buscador'
        });
    }
</script>

<script>
    async function registrarPago(cobro) {
        const btn = document.getElementById('btn-registrar');
        try {
            if (btn) btn.disabled = true;
            if (typeof window.showElLoading === 'function') {
                window.showElLoading(btn);
            }

            const res = await fetch('/api/cobro/pagar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    cobro_id: cobro.id,
                    monto: cobro.monto,
                    deudor_id: cobro.deudor_id,
                    acreedor_id: cobro.acreedor_id,
                    servicio_id: cobro.servicio_id ?? null
                })
            });

            const json = await res.json();

            if (json.error) {
                mostrarMensaje('Error', '❌ ' + json.error, 'danger');
            } else {
                mostrarMensaje('Éxito', 'El pago se ha registrado correctamente', 'success');
                const modalEl = document.getElementById('modalCobro');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                cargarPendientes(paginaActual);
            }

            if (btn) btn.disabled = false;
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(btn);
            }

        } catch (err) {
            console.error(err);
            mostrarMensaje('Error', '❌ Error de conexión', 'danger');
            if (btn) btn.disabled = false;
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(btn);
            }
        }
    }

    // Event to open modal
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-cobro')) return;

        const cobro = JSON.parse(e.target.dataset.cobro);

        const body = document.getElementById("modal-body-cobro");

        body.innerHTML = `
            <p><b>Deudor:</b> ${cobro.deudor}</p>
            <p><b>Monto:</b> $${cobro.monto}</p>
            <p><b>Acreedor:</b> ${cobro.acreedor}</p>
            <p><b>Fecha de pago:</b> Hoy</p>
        `;

        document.getElementById("btn-registrar").onclick = () => registrarPago(cobro);
        document.getElementById("btn-detallar").onclick = () => {
            window.location.href = `/cobro/${cobro.id}`;
        };

        const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
        modal.show();
    });

    function mostrarMensaje(titulo, mensaje, tipo = 'success') {
        const modalHtml = `
            <div class="modal fade" id="modalMensaje" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header ${tipo === 'success' ? 'bg-success text-white' : 'bg-danger text-white'}">
                            <h5 class="modal-title">${titulo}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${mensaje}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existing = document.getElementById('modalMensaje');
        if (existing) existing.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('modalMensaje'));
        modal.show();
    }
</script>
@endpush