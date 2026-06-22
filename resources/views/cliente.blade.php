@extends('layouts.app')
@section('title', 'Ficha del Cliente')
@section('content')
<div class="row">
    <div class="col-12">
        <h1>{{ $cliente->nombre }}</h1>
    </div>
</div>
@include('components.pendientes', ['pendientes' => $pendientes, 'cliente' => $cliente, 'clienteOptions' => $clienteOptions, 'tiposCobroDisponibles' => $tiposCobroDisponibles])
@include('cliente.modal.show', ['cliente' => $cliente])
<a href="{{ route('cliente.reparaciones', $cliente->id) }}" class="btn btn-sm btn-primary">Historial de movimientos</a>
<a href="{{ route('cliente.contratos', $cliente->id) }}" class="btn btn-sm btn-primary">Contratos</a>

<!-- Cobro detail modal -->
<div class="modal fade" id="modalCobro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body-cobro"></div>
            <div class="modal-footer">
                <button id="btn-registrar" class="btn btn-primary">Registrar pago</button>
            </div>
        </div>
    </div>
</div>
@include('components.guarantee-refund-modal')
@endsection

@push('scripts')
<script>
    let paginaActual = 1;
    const CLIENTE_ID = {{ $cliente->id }};

    const FICHA_POR_PAGINA = 3;

    // ---- AJAX: fetch and refresh ----
    async function cargarFichaPendientes(pagina = 1) {
        const section = document.getElementById('pendientes-section');
        if (section && typeof window.showElLoading === 'function') {
            window.showElLoading(section);
        }
        try {
            const res = await fetch(`/api/cliente/${CLIENTE_ID}/pendientes?pagina=${pagina}&por_pagina=${FICHA_POR_PAGINA}`);
            const json = await res.json();
            paginaActual = json.pagina || pagina;
            // Adjust pagination if current page is now empty
            if (json.total_paginas > 0 && paginaActual > json.total_paginas) {
                paginaActual = json.total_paginas;
                return await cargarFichaPendientes(paginaActual);
            }
            renderFichaPendientes(json);
        } catch (err) {
            console.error('Error loading pendientes:', err);
        } finally {
            if (section && typeof window.hideElLoading === 'function') window.hideElLoading(section);
        }
    }

    function cobroColor(estado) {
        if (estado === 'Pendiente') return 'warning';
        if (estado === 'Vencido') return 'danger';
        if (estado === 'Incompleto') return 'info';
        return 'secondary';
    }

    function serializeCobro(cobro) {
        return escHtml(JSON.stringify(cobro)).replace(/'/g, '&#39;');
    }

    function renderCobros(lista = []) {
        if (!lista.length) return '<span class="text-muted">—</span>';

        return lista.map(c => {
            const color = cobroColor(c.estado);
            return `
                <div class="mb-1">
                    <button
                        type="button"
                        class="btn btn-sm btn-${color} w-100 text-center btn-cobro"
                        data-cobro='${serializeCobro(c)}'
                    >
                        ${escHtml(c.concepto || 'Sin tipo')}
                    </button>
                </div>
            `;
        }).join('');
    }

    // ---- Render: dashboard-like ficha table ----
    function renderFichaPendientes(json) {
        const section = document.getElementById('pendientes-section');
        if (!section) return;

        if (!json.data || json.data.length === 0) {
            section.innerHTML = '<div class="alert alert-light border">No hay transacciones pendientes por el momento.</div>';
            return;
        }

        const hayCol = {
            arrendador: json.data.some(item => item.arrendador && item.arrendador.length > 0),
            arrendatario: json.data.some(item => item.arrendatario && item.arrendatario.length > 0),
            corredor: json.data.some(item => item.corredor && item.corredor.length > 0)
        };

        const columnCount = 1 + Object.values(hayCol).filter(Boolean).length;

        let html = `
            <div class="card" id="ficha-pendientes-container">
                <div class="table-responsive" id="ficha-pendientes-wrapper">
                    <table class="table mb-0 text-nowrap table-hover table-card-mobile pendientes-dashboard-table ficha-pendientes-table">
                        <thead class="table-light border-light">
                            <tr>
                                <th><b>Dirección</b></th>
                                ${hayCol.arrendador ? '<th data-col="arrendador"><b>Cobros al Arrendador</b></th>' : ''}
                                ${hayCol.arrendatario ? '<th data-col="arrendatario"><b>Cobros al Arrendatario</b></th>' : ''}
                                ${hayCol.corredor ? '<th data-col="corredor"><b>Cobros al Corredor</b></th>' : ''}
                            </tr>
                        </thead>
                        <tbody id="body-ficha-pendientes">
        `;

        json.data.forEach(grupo => {
            html += `<tr>`;
            html += `<td><a href="/propiedad/ficha/${grupo.id}">${escHtml(grupo.direccion || 'Sin propiedad')}</a></td>`;
            if (hayCol.arrendador) html += `<td class="td-cobros">${renderCobros(grupo.arrendador || [])}</td>`;
            if (hayCol.arrendatario) html += `<td class="td-cobros">${renderCobros(grupo.arrendatario || [])}</td>`;
            if (hayCol.corredor) html += `<td class="td-cobros">${renderCobros(grupo.corredor || [])}</td>`;
            html += `</tr>`;
        });

        html += `</tbody>`;
        if (json.total_paginas > 1) {
            html += `<tfoot><tr><td colspan="${columnCount}" class="border-bottom-0">${renderFichaPaginacion(json.pagina, json.total_paginas)}</td></tr></tfoot>`;
        }

        html += `</table></div></div>`;

        section.innerHTML = html;
        labelFichaPendientesTable();
    }

    function labelFichaPendientesTable() {
        const table = document.querySelector('.ficha-pendientes-table');
        if (!table) return;
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach(tr => {
            tr.querySelectorAll('td').forEach((td, i) => {
                if (headers[i]) td.setAttribute('data-label', headers[i]);
            });
        });
    }

    // ---- Render: pagination ----
    function renderFichaPaginacion(pagina, totalPaginas) {
        let html = '<nav><ul class="pagination pagination-sm">';
        for (let i = 1; i <= totalPaginas; i++) {
            html += `<li class="page-item ${i === pagina ? 'active' : ''}"><a class="page-link" href="#" onclick="cargarFichaPendientes(${i}); return false;">${i}</a></li>`;
        }
        html += '</ul></nav>';
        return html;
    }

    // ---- Escape HTML special chars ----
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ---- Registrar pago via API ----
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
                mostrarMensaje('Error', '\u274c ' + json.error, 'danger');
            } else {
                mostrarMensaje('\u00c9xito', 'El pago se ha registrado correctamente', 'success');
                const modalEl = document.getElementById('modalCobro');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // AJAX refresh instead of full reload
                await cargarFichaPendientes(paginaActual);
            }

        } catch (err) {
            console.error(err);
            mostrarMensaje('Error', '\u274c Error de conexi\u00f3n', 'danger');
        } finally {
            if (btn) btn.disabled = false;
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(btn);
            }
        }
    }

    // ---- Click handler for btn-cobro (opens modal) ----
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-cobro')) return;

        const cobro = JSON.parse(e.target.dataset.cobro);

        if (cobro.is_guarantee_refund && typeof window.openGuaranteeRefundModal === 'function') {
            openGuaranteeRefundModal(cobro);
            return;
        }

        const body = document.getElementById('modal-body-cobro');

        const montoFormateado = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(cobro.monto);

        let fechaPagoTexto = 'No definida';
        if (cobro.fecha_cobro) {
            const fecha = new Date(cobro.fecha_cobro);
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            fechaPagoTexto = `${fecha.getDate()} de ${meses[fecha.getMonth()]} de ${fecha.getFullYear()}`;
        }

        const deudorLink = cobro.deudor_id ? `<a href="/cliente/ficha/${cobro.deudor_id}" class="text-decoration-none">${cobro.deudor}</a>` : cobro.deudor;
        const acreedorLink = cobro.acreedor_id ? `<a href="/cliente/ficha/${cobro.acreedor_id}" class="text-decoration-none">${cobro.acreedor}</a>` : cobro.acreedor;

        body.innerHTML = `
            <p><b>Tipo de cobro:</b> ${cobro.tipo}</p>
            <p><b>Deudor:</b> ${deudorLink}</p>
            <p><b>Acreedor:</b> ${acreedorLink}</p>
            <p><b>Monto:</b> ${montoFormateado}</p>
            <p><b>Fecha de pago:</b> ${fechaPagoTexto}</p>
        `;

        document.getElementById('btn-registrar').onclick = () => registrarPago(cobro);

        const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
        modal.show();
    });

    // ---- Flash modal ----
    window.afterGuaranteeRefundFinalized = function () {
        cargarFichaPendientes(paginaActual);
    };

    window.mostrarMensaje = function mostrarMensaje(titulo, mensaje, tipo = 'success') {
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

@include('components.guarantee-refund-scripts')
@endpush
