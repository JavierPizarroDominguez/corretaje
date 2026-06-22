@once
<div class="modal fade" id="modalGarantiaRefund" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Devolver garantía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body-garantia-refund">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Garantía base</div>
                            <strong data-refund-base>$0</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Plazo restante</div>
                            <strong data-refund-plazo>0 días</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Monto a devolver</div>
                            <strong data-refund-total>$0</strong>
                        </div>
                    </div>
                </div>

                <div class="alert alert-danger d-none" role="alert" data-refund-error></div>

                <h6>Descuentos</h6>
                <p class="text-muted small">Agrega los descuentos finales antes de devolver la garantía. Estos descuentos se guardan recién al confirmar.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-2 table-card-mobile pendientes-dashboard-table ficha-pendientes-table garantia-refund-ajustes-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Detalle</th>
                                <th>Monto</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody data-refund-discounts>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-refund-add>Agregar descuento</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-devolver-garantia">Devolver garantía</button>
            </div>
        </div>
    </div>
</div>
@endonce
