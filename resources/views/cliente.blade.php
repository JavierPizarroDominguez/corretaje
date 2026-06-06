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
@include('components.transacciones-propiedad', ['transacciones' => $transacciones])
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
@endsection

@push('scripts')
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
                mostrarMensaje('Error', '\u274c ' + json.error, 'danger');
            } else {
                mostrarMensaje('\u00c9xito', 'El pago se ha registrado correctamente', 'success');
                const modalEl = document.getElementById('modalCobro');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                location.reload();
            }

            if (btn) btn.disabled = false;
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(btn);
            }

        } catch (err) {
            console.error(err);
            mostrarMensaje('Error', '\u274c Error de conexi\u00f3n', 'danger');
            if (btn) btn.disabled = false;
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(btn);
            }
        }
    }

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-cobro')) return;

        const cobro = JSON.parse(e.target.dataset.cobro);

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