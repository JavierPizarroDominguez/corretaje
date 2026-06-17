@php
    $formatMoney = fn ($value) => is_numeric($value) ? '$' . number_format((int) $value, 0, ',', '.') : 'Sin información';
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d-m-Y') : 'Indefinido';
    $today = \Illuminate\Support\Carbon::now()->format('d-m-Y');
    $participantClient = function ($contrato, $role) {
        $relationClient = optional($contrato->{$role})->cliente;

        return $relationClient ?: optional(
            $contrato->participante_contratos->firstWhere('rol', ucfirst($role))
        )->cliente;
    };
@endphp

@foreach($contratosVigentes as $contrato)
    @php
        $arrendador = $participantClient($contrato, 'arrendador');
        $arrendatario = $participantClient($contrato, 'arrendatario');
        $corredor = $participantClient($contrato, 'corredor');
    @endphp

    <div class="card mb-4">

        <div class="card-header">
            <h5>
                Contrato —
                {{ $contrato->unidad->propiedad->direccion ?? 'Sin propiedad' }}
            </h5>
        </div>

        <div class="card-body">

            <table class="table table-bordered">
                <tr>
                    <td><b>Renta</b></td>
                    <td>{{ $formatMoney($contrato->renta) }}</td>
                </tr>
                <tr>
                    <td><b>Día de pago</b></td>
                    <td>{{ $contrato->dia_pago }}</td>
                </tr>
                <tr>
                    <td><b>Arrendador</b></td>
                    <td>
                        @if($arrendador)
                            <a href="{{ route('fichacliente.show', $arrendador->id) }}">
                                {{ $arrendador->nombre }}
                            </a>
                        @else
                            Sin arrendador
                        @endif
                    </td>
                </tr>

                <tr>
                    <td><b>Arrendatario</b></td>
                    <td>
                        @if($arrendatario)
                            <a href="{{ route('fichacliente.show', $arrendatario->id) }}">
                                {{ $arrendatario->nombre }}
                            </a>
                        @else
                            Sin arrendatario
                        @endif
                    </td>
                </tr>

                <tr>
                    <td><b>Corredor</b></td>
                    <td>
                        @if($corredor)
                            <a href="{{ route('fichacliente.show', $corredor->id) }}">
                                {{ $corredor->nombre }}
                            </a>
                        @else
                            Sin corredor
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><b>Garantía</b></td>
                    <td>{{ $formatMoney($contrato->garantia) }}</td>
                </tr>

                <tr>
                    <td><b>Fecha Inicio</b></td>
                    <td>{{ $formatDate($contrato->fecha_inicio) }}</td>
                </tr>

                <tr>
                    <td><b>Fecha Término</b></td>
                    <td>{{ $formatDate($contrato->fecha_termino) }}</td>
                </tr>
            </table>

            <div class="mt-3">
                <button type="button"
                        class="btn btn-sm btn-danger"
                        onclick="abrirModal({titulo: 'Término de contrato y devolución de garantía', vista: 'vista-terminar-contrato-{{ $contrato->id }}', size: 'modal-xl'}); window.initTerminacionContratoPreview && window.initTerminacionContratoPreview({{ $contrato->id }});">
                    <i class="ti ti-door-exit"></i> Terminar contrato
                </button>
            </div>

            <div id="vista-terminar-contrato-{{ $contrato->id }}" class="d-none">
                <div class="terminacion-preview" data-contrato-id="{{ $contrato->id }}" data-garantia="{{ (int) $contrato->garantia }}">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Fecha de inicio</div>
                                <strong>{{ $formatDate($contrato->fecha_inicio) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Fecha de término</div>
                                <strong>{{ $today }}</strong>
                            </div>
                        </div>
                    </div>

                    @if($contrato->cobros->isNotEmpty())
                    <div class="alert alert-warning" role="alert">
                        <strong>¡Atención!</strong>
                        La propiedad aún tiene cobros pendientes. Revisa cada cobro antes de finalizar el contrato.
                    </div>
                    <h6>Cobros pendientes</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0">
                                    <tbody>
                                    @foreach($contrato->cobros as $cobro)
                                        <tr class="terminacion-row" data-sign="charge" data-amount="{{ (int) $cobro->monto }}">
                                            <td>{{ $cobro->tipo ?? 'Cobro pendiente' }}</td>
                                            <td class="text-end">{{ $formatMoney($cobro->monto) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No hay cobros pendientes para este contrato.</p>
                    @endif

                    <h6>Descuentos a la devolución de garantía</h6>
                    <p>Puedes agregar descuentos a la devolución de garantía, como cargos por aseo o reparaciones. Selecciona el concepto, agrega un detalle y el monto correspondiente. El sistema calculará automáticamente el total a devolver al arrendatario.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Detalle</th>
                                    <th>Monto</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="terminacion-ajustes">
                                <tr class="terminacion-row terminacion-ajuste" data-sign="charge" data-amount="0">
                                    <td>
                                        <select class="form-select form-select-sm terminacion-sign">
                                            <option value="charge" selected>Aseo final</option>
                                            <option value="refund">Reparación</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm terminacion-description"></td>
                                    <td><input type="text" class="form-control form-control-sm terminacion-amount" value="$0"></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger terminacion-remove">Quitar</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary terminacion-add">Agregar descuento</button>

                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Total descuentos</div>
                                <strong class="terminacion-neto">$0</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Garantía</div>
                                <strong class="terminacion-garantia">{{ $formatMoney($contrato->garantia) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Monto a devolver al arrendatario</div>
                                <strong class="terminacion-total">{{ $formatMoney($contrato->garantia) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endforeach

@once
    <script>
        (function () {
            function parseCLP(value) {
                if (window.stripCLP) return parseInt(window.stripCLP(value), 10) || 0;
                return parseInt(String(value || '').replace(/\D/g, ''), 10) || 0;
            }

            function formatCLP(value) {
                if (window.formatCLP) return window.formatCLP(value);
                return '$' + (parseInt(value, 10) || 0).toLocaleString('es-CL');
            }

            function recalculate(preview) {
                var garantia = parseInt(preview.dataset.garantia || '0', 10) || 0;
                var charges = 0;
                var refunds = 0;

                preview.querySelectorAll('.terminacion-row').forEach(function (row) {
                    var input = row.querySelector('.terminacion-amount');
                    var select = row.querySelector('.terminacion-sign');
                    var amount = input ? parseCLP(input.value) : (parseInt(row.dataset.amount || '0', 10) || 0);
                    var sign = select ? select.value : row.dataset.sign;

                    row.dataset.amount = amount;
                    row.dataset.sign = sign;
                    if (sign === 'refund') refunds += amount;
                    else charges += amount;
                });

                var net = charges - refunds;
                preview.querySelector('.terminacion-neto').textContent = formatCLP(net);
                preview.querySelector('.terminacion-devoluciones').textContent = formatCLP(refunds);
                preview.querySelector('.terminacion-total').textContent = formatCLP(garantia - net);
            }

            function addAdjustment(preview) {
                var tbody = preview.querySelector('.terminacion-ajustes');
                var row = tbody.querySelector('.terminacion-ajuste').cloneNode(true);
                row.dataset.sign = 'charge';
                row.dataset.amount = '0';
                row.querySelector('.terminacion-sign').value = 'charge';
                row.querySelector('.terminacion-description').value = '';
                row.querySelector('.terminacion-amount').value = '$0';
                tbody.appendChild(row);
                recalculate(preview);
            }

            window.initTerminacionContratoPreview = function (contractId) {
                setTimeout(function () {
                    var preview = document.querySelector('#modalPrincipalBody .terminacion-preview[data-contrato-id="' + contractId + '"]');
                    if (preview) recalculate(preview);
                }, 0);
            };

            document.addEventListener('input', function (event) {
                var preview = event.target.closest('.terminacion-preview');
                if (!preview) return;
                if (event.target.classList.contains('terminacion-amount') && window.handleCLPInput) {
                    window.handleCLPInput(event.target);
                }
                recalculate(preview);
            });

            document.addEventListener('change', function (event) {
                var preview = event.target.closest('.terminacion-preview');
                if (preview) recalculate(preview);
            });

            document.addEventListener('click', function (event) {
                var preview = event.target.closest('.terminacion-preview');
                if (!preview) return;
                if (event.target.classList.contains('terminacion-add')) addAdjustment(preview);
                if (event.target.classList.contains('terminacion-remove')) {
                    var row = event.target.closest('.terminacion-ajuste');
                    if (row && preview.querySelectorAll('.terminacion-ajuste').length > 1) row.remove();
                    recalculate(preview);
                }
            });
        })();
    </script>
@endonce
