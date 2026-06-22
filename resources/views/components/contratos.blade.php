@php
    $contratosVigentes = $contratosVigentes ?? collect();
    $contratosTerminados = $contratosTerminados ?? collect();
    $formatMoney = fn ($value) => is_numeric($value) ? '$' . number_format((int) $value, 0, ',', '.') : 'Sin información';
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d-m-Y') : 'Indefinido';
    $today = \Illuminate\Support\Carbon::now()->format('d-m-Y');
    $pendingStates = ['pendiente', 'vencido', 'incompleto'];
    $participantClient = function ($contrato, $role) {
        $relationClient = optional($contrato->{$role})->cliente;

        return $relationClient ?: optional(
            $contrato->participante_contratos->firstWhere('rol', ucfirst($role))
        )->cliente;
    };
    $normalizeCobroState = fn ($state) => ucfirst(strtolower((string) $state));
    $buildCobroPayload = function ($cobro) use ($normalizeCobroState) {
        $deudor = optional($cobro->participante_cobros->firstWhere('rol', 'Deudor'));
        $acreedor = optional($cobro->participante_cobros->firstWhere('rol', 'Acreedor'));
        $fechaCobro = $cobro->fecha_cobro ? \Illuminate\Support\Carbon::parse($cobro->fecha_cobro) : null;

        return [
            'id' => $cobro->id,
            'estado' => $normalizeCobroState($cobro->estado),
            'tipo' => $cobro->tipo,
            'monto' => (int) $cobro->monto,
            'deudor' => optional($deudor->cliente)->nombre ?? 'Sin deudor',
            'deudor_id' => $deudor->Cliente_id,
            'acreedor' => optional($acreedor->cliente)->nombre ?? 'Sin acreedor',
            'acreedor_id' => $acreedor->Cliente_id,
            'servicio_id' => $cobro->Servicio_id,
            'fecha_cobro' => optional($fechaCobro)->toIso8601String(),
            'concepto' => \App\Services\CobroConceptoFormatter::format($cobro->tipo ?? 'Cobro pendiente', $fechaCobro),
        ];
    };
@endphp

@foreach([
    ['title' => 'Contratos Vigentes', 'contracts' => $contratosVigentes, 'showTerminationAction' => true, 'emptyMessage' => 'No hay contratos vigentes.'],
    ['title' => 'Contratos Terminados', 'contracts' => $contratosTerminados, 'showTerminationAction' => false, 'emptyMessage' => 'No hay contratos terminados.'],
] as $contractSection)
<section class="mb-4">
    <h4 class="mb-3">{{ $contractSection['title'] }}</h4>

    @if($contractSection['contracts']->count() === 0)
        <p class="text-muted">{{ $contractSection['emptyMessage'] }}</p>
    @else
@foreach($contractSection['contracts'] as $contrato)
    @php
        $showTerminationAction = $contractSection['showTerminationAction'];
        $arrendador = $participantClient($contrato, 'arrendador');
        $arrendatario = $participantClient($contrato, 'arrendatario');
        $corredor = $participantClient($contrato, 'corredor');
        $propiedad = optional($contrato->unidad)->propiedad;
        $unidadCount = $propiedad ? \App\Models\Unidad::where('Propiedad_id', $propiedad->id)->count() : 0;
        $headingLocation = $unidadCount > 1
            ? trim(($contrato->unidad->nombre ?? 'Sin unidad') . ' — ' . ($propiedad->direccion ?? 'Sin propiedad'))
            : ($propiedad->direccion ?? 'Sin propiedad');
        $pendingCobros = $contrato->cobros->filter(fn ($cobro) => in_array(strtolower((string) $cobro->estado), $pendingStates, true));
        $pendingGroups = ['arrendador' => [], 'arrendatario' => [], 'corredor' => []];

        foreach ($pendingCobros as $cobro) {
            $payload = $buildCobroPayload($cobro);
            $deudorContrato = $contrato->participante_contratos->firstWhere('Cliente_id', $payload['deudor_id']);
            $role = strtolower(optional($deudorContrato)->rol ?? 'arrendatario');
            if (! array_key_exists($role, $pendingGroups)) $role = 'arrendatario';
            $pendingGroups[$role][] = $payload;
        }

        $hasArrendadorCobros = count($pendingGroups['arrendador']) > 0;
        $hasArrendatarioCobros = count($pendingGroups['arrendatario']) > 0;
        $hasCorredorCobros = count($pendingGroups['corredor']) > 0;
        $terminatedModalTitle = 'Contrato de ' . ($arrendatario?->nombre ?? 'Sin arrendatario');
    @endphp

    @if(! $showTerminationAction)
        <div class="mb-1">
            <button type="button"
                    class="btn btn-sm btn-secondary w-100 text-center"
                    onclick="abrirModal({titulo: @js($terminatedModalTitle), vista: 'vista-contrato-terminado-{{ $contrato->id }}', size: 'modal-lg'})">
                {{ $terminatedModalTitle }}
            </button>
        </div>

        <div class="d-none">
            <div id="vista-contrato-terminado-{{ $contrato->id }}">
                <h5 class="mb-3">
                    Contrato —
                    {{ $headingLocation }}
                </h5>
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
            </div>
        </div>
        @continue
    @endif

    <div class="card mb-4" @if($showTerminationAction) data-terminacion-contract-card="{{ $contrato->id }}" @endif>

        <div class="card-header">
            <h5>
                Contrato —
                {{ $headingLocation }}
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

            @if($showTerminationAction)
            <div class="mt-3">
                <button type="button"
                        class="btn btn-sm btn-danger"
                        onclick="abrirModal({titulo: 'Término de contrato y devolución de garantía', vista: 'vista-terminar-contrato-{{ $contrato->id }}', size: 'modal-xl'}); window.initTerminacionContratoPreview && window.initTerminacionContratoPreview({{ $contrato->id }});">
                    <i class="ti ti-door-exit"></i> Terminar contrato
                </button>
            </div>

            <div id="vista-terminar-contrato-{{ $contrato->id }}" class="d-none">
                <div class="terminacion-preview" data-contrato-id="{{ $contrato->id }}" data-garantia="{{ (int) $contrato->garantia }}">

                    <h5>Vista previa de término de contrato</h5>
                    <p class="text-muted mb-3">Esta vista previa no termina el contrato ni guarda cambios. Inspeccioná la propiedad antes de confirmar cualquier devolución; servicios y gastos comunes proporcionales son avisos automáticos de esta vista previa.</p>

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
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Garantía original</div>
                                <strong>{{ $formatMoney($contrato->garantia) }}</strong>
                            </div>
                        </div>
                    </div>

                    @if($pendingCobros->isNotEmpty())
                    <div class="border border-warning rounded bg-warning-subtle text-warning-emphasis p-3 mb-3" role="status">
                        <strong>¡Atención!</strong>
                        La propiedad aún tiene cobros pendientes. Revisa cada cobro antes de finalizar el contrato.
                    </div>
                    <h6>Cobros pendientes</h6>
                        <div class="table-responsive mb-3" id="terminacion-pendientes-wrapper-{{ $contrato->id }}">
                            <table class="table mb-0 text-nowrap table-hover table-card-mobile pendientes-dashboard-table ficha-pendientes-table terminacion-pendientes-table">
                                <thead class="table-light border-light">
                                    <tr>
                                        <th><b>Contrato</b></th>
                                        @if($hasArrendadorCobros)<th data-col="arrendador"><b>Cobros al Arrendador</b></th>@endif
                                        @if($hasArrendatarioCobros)<th data-col="arrendatario"><b>Cobros al Arrendatario</b></th>@endif
                                        @if($hasCorredorCobros)<th data-col="corredor"><b>Cobros al Corredor</b></th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $headingLocation }}</td>
                                        @if($hasArrendadorCobros)<td class="td-cobros text-center">@include('components._pendientes-cobros-buttons', ['cobros' => $pendingGroups['arrendador']])</td>@endif
                                        @if($hasArrendatarioCobros)<td class="td-cobros text-center">@include('components._pendientes-cobros-buttons', ['cobros' => $pendingGroups['arrendatario']])</td>@endif
                                        @if($hasCorredorCobros)<td class="td-cobros text-center">@include('components._pendientes-cobros-buttons', ['cobros' => $pendingGroups['corredor']])</td>@endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No hay cobros pendientes para este contrato.</p>
                    @endif

                    <h6>Descuentos a la devolución de garantía</h6>
                    <p>Puedes agregar descuentos a la devolución de garantía, como cargos por aseo o reparaciones. Selecciona el concepto, agrega un detalle y el monto correspondiente. El sistema calculará automáticamente el total a devolver al arrendatario.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-2 table-card-mobile pendientes-dashboard-table ficha-pendientes-table terminacion-ajustes-table">
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
                                            <option value="Aseo Final" selected>Aseo final</option>
                                            <option value="Reparación">Reparación</option>
                                            <option value="Extra">Extra</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm terminacion-description" placeholder="Detalle"></td>
                                    <td><input type="text" class="form-control form-control-sm terminacion-amount" value="$0"></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger terminacion-remove">Quitar</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary terminacion-add">Agregar descuento</button>

                    <div class="alert alert-danger mt-3 d-none terminacion-validation-error" role="alert"></div>

                    <div class="border border-warning rounded bg-warning-subtle text-warning-emphasis p-3 mt-3 d-none" role="status" data-terminacion-full-refund-warning="true">
                        <strong>¡Atención!</strong>
                        se devolverá la garantía en su totalidad al arrendatario. ¿Está seguro que no hay reparaciones o aseo que pagar?
                    </div>

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
                        <button type="button" class="btn btn-danger terminacion-confirm">Terminar contrato</button>
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>

@endforeach
    @endif

    @if(method_exists($contractSection['contracts'], 'links'))
        <div class="mt-3">
            {{ $contractSection['contracts']->links() }}
        </div>
    @endif
</section>
@endforeach

@once
    <div class="modal fade" id="modalCobro" tabindex="-1" data-terminacion-stacked-modal="true" aria-hidden="true">
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
@endonce

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
                var discounts = 0;
                var warning = preview.querySelector('[data-terminacion-full-refund-warning="true"]');
                var validationError = preview.querySelector('.terminacion-validation-error');
                var adjustmentRows = preview.querySelectorAll('.terminacion-ajuste');

                adjustmentRows.forEach(function (row) {
                    var input = row.querySelector('.terminacion-amount');
                    var amount = input ? parseCLP(input.value) : 0;

                    row.dataset.amount = amount;
                    discounts += amount;
                });

                preview.querySelector('.terminacion-neto').textContent = formatCLP(discounts);
                preview.querySelector('.terminacion-total').textContent = formatCLP(garantia - discounts);
                if (validationError && discounts <= garantia) {
                    validationError.classList.add('d-none');
                    validationError.textContent = '';
                }
                if (warning) warning.classList.toggle('d-none', adjustmentRows.length > 0);
            }

            function collectTerminationDiscounts(preview) {
                return Array.from(preview.querySelectorAll('.terminacion-ajuste')).map(function (row) {
                    var concept = row.querySelector('.terminacion-sign');
                    var detail = row.querySelector('.terminacion-description');
                    var amount = row.querySelector('.terminacion-amount');

                    return {
                        concepto: concept ? concept.value : 'Extra',
                        detalle: detail ? detail.value.trim() : '',
                        monto: amount ? parseCLP(amount.value) : 0
                    };
                }).filter(function (discount) {
                    return discount.monto > 0;
                });
            }

            function totalTerminationDiscounts(preview) {
                return collectTerminationDiscounts(preview).reduce(function (total, discount) {
                    return total + discount.monto;
                }, 0);
            }

            function validateTerminationDiscounts(preview) {
                var garantia = parseInt(preview.dataset.garantia || '0', 10) || 0;
                var validationError = preview.querySelector('.terminacion-validation-error');
                var valid = totalTerminationDiscounts(preview) <= garantia;

                if (validationError) {
                    validationError.classList.toggle('d-none', valid);
                    validationError.textContent = valid ? '' : 'Los descuentos no pueden superar la garantía.';
                }

                return valid;
            }

            function createAdjustmentRow() {
                var row = document.createElement('tr');
                row.className = 'terminacion-row terminacion-ajuste';
                row.dataset.sign = 'charge';
                row.dataset.amount = '0';
                row.innerHTML = '<td><select class="form-select form-select-sm terminacion-sign">'
                    + '<option value="Aseo Final" selected>Aseo final</option>'
                    + '<option value="Reparación">Reparación</option>'
                    + '<option value="Extra">Extra</option>'
                    + '</select></td>'
                    + '<td><input type="text" class="form-control form-control-sm terminacion-description" placeholder="Detalle"></td>'
                    + '<td><input type="text" class="form-control form-control-sm terminacion-amount" value="$0"></td>'
                    + '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger terminacion-remove">Quitar</button></td>';

                return row;
            }

            function addAdjustment(preview) {
                var tbody = preview.querySelector('.terminacion-ajustes');
                var sourceRow = tbody.querySelector('.terminacion-ajuste');
                var row = sourceRow ? sourceRow.cloneNode(true) : createAdjustmentRow();
                row.dataset.sign = 'charge';
                row.dataset.amount = '0';
                row.querySelector('.terminacion-sign').value = 'Aseo Final';
                var description = row.querySelector('.terminacion-description');
                description.value = '';
                description.placeholder = 'Detalle';
                row.querySelector('.terminacion-amount').value = '$0';
                tbody.appendChild(row);
                recalculate(preview);
            }

            function labelTerminacionTables(preview) {
                preview.querySelectorAll('.terminacion-pendientes-table, .terminacion-ajustes-table').forEach(function (table) {
                    var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                        return th.textContent.trim();
                    });
                    table.querySelectorAll('tbody tr').forEach(function (tr) {
                        tr.querySelectorAll('td').forEach(function (td, index) {
                            if (headers[index]) td.setAttribute('data-label', headers[index]);
                        });
                    });
                });
            }

            function removeAdjustment(row, preview) {
                row.remove();
                recalculate(preview);
            }

            function nextTerminacionModalZIndex(modalEl) {
                var visibleModals = Array.from(document.querySelectorAll('.modal.show')).filter(function (visibleModal) {
                    return visibleModal !== modalEl;
                }).length;

                return 1055 + ((visibleModals + 1) * 20);
            }

            function prepareTerminacionModalStack(modalEl) {
                if (!modalEl) return;

                modalEl.style.zIndex = nextTerminacionModalZIndex(modalEl);
            }

            function applyTerminacionModalStack(modalEl) {
                if (!modalEl) return;

                var zIndex = parseInt(modalEl.style.zIndex, 10) || nextTerminacionModalZIndex(modalEl);
                modalEl.style.zIndex = zIndex;

                setTimeout(function () {
                    var backdrops = document.querySelectorAll('.modal-backdrop:not([data-terminacion-stacked])');
                    var backdrop = backdrops[backdrops.length - 1];
                    if (backdrop) {
                        backdrop.dataset.terminacionStacked = 'true';
                        backdrop.style.zIndex = zIndex - 10;
                    }
                }, 0);
            }

            function restoreTerminacionParentModalState() {
                var visibleModals = document.querySelectorAll('.modal.show');

                if (visibleModals.length > 0) {
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';

                    if (visibleModals.length === 1) {
                        document.querySelectorAll('.modal-backdrop[data-terminacion-stacked]').forEach(function (backdrop) {
                            backdrop.remove();
                        });
                        ensureTerminacionParentBackdrop(visibleModals[0]);
                    }
                } else {
                    document.querySelectorAll('.terminacion-parent-backdrop').forEach(function (backdrop) {
                        backdrop.remove();
                    });
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            }

            function ensureTerminacionParentBackdrop(parentModal) {
                if (!parentModal || parentModal.id !== 'modalPrincipal') return;
                if (document.querySelector('.modal-backdrop:not([data-terminacion-stacked])')) return;

                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show terminacion-parent-backdrop';
                backdrop.style.zIndex = (parseInt(parentModal.style.zIndex, 10) || 1055) - 10;
                document.body.appendChild(backdrop);
            }

            function showMessage(titleText, message, type) {
                var modalEl = document.getElementById('flashModal');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                var header = document.getElementById('flashHeader');
                var title = document.getElementById('flashTitle');
                var body = document.getElementById('flashBody');
                if (header) {
                    header.classList.remove('bg-success', 'bg-danger', 'text-white');
                    header.classList.add(type === 'success' ? 'bg-success' : 'bg-danger', 'text-white');
                }
                if (title) title.innerText = titleText;
                if (body) body.innerText = message;
                modal.show();
            }

            async function registrarPago(cobro) {
                var btn = document.getElementById('btn-registrar');
                try {
                    if (btn) {
                        btn.disabled = true;
                        if (typeof window.showElLoading === 'function') window.showElLoading(btn);
                    }

                    var res = await fetch('/api/cobro/pagar', {
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
                            servicio_id: cobro.servicio_id || null
                        })
                    });

                    var json = await res.json();
                    if (json.error) {
                        showMessage('Error', json.error, 'danger');
                    } else {
                        showMessage('Éxito', 'El pago se ha registrado correctamente', 'success');
                        var modalEl = document.getElementById('modalCobro');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                } catch (error) {
                    showMessage('Error', 'Error de conexión', 'danger');
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        if (typeof window.hideElLoading === 'function') window.hideElLoading(btn);
                    }
                }
            }

            function resolveTerminationError(json) {
                if (!json) return 'No se pudo terminar el contrato.';
                if (json.error) return json.error;
                if (json.message) return json.message;
                if (json.errors) {
                    var firstKey = Object.keys(json.errors)[0];
                    if (firstKey && json.errors[firstKey] && json.errors[firstKey][0]) return json.errors[firstKey][0];
                }

                return 'No se pudo terminar el contrato.';
            }

            function removeTerminatedContractFromActiveUi(preview) {
                var contractId = preview.dataset.contratoId;
                var card = document.querySelector('[data-terminacion-contract-card="' + contractId + '"]');
                if (card) card.remove();
                var modalEl = document.getElementById('modalPrincipal');
                var modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                if (modal) modal.hide();
            }

            async function terminateContract(preview, btn) {
                if (!validateTerminationDiscounts(preview)) {
                    return;
                }

                var contractId = preview.dataset.contratoId;
                try {
                    btn.disabled = true;
                    if (typeof window.showElLoading === 'function') window.showElLoading(btn);

                    var res = await fetch('/api/contratos/' + contractId + '/terminar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            descuentos: collectTerminationDiscounts(preview)
                        })
                    });
                    var json = await res.json();

                    if (!res.ok || json.error || json.errors) {
                        showMessage('Error', resolveTerminationError(json), 'danger');
                        return;
                    }

                    showMessage('Éxito', 'El contrato se terminó correctamente.', 'success');
                    removeTerminatedContractFromActiveUi(preview);
                } catch (error) {
                    showMessage('Error', 'Error de conexión', 'danger');
                } finally {
                    btn.disabled = false;
                    if (typeof window.hideElLoading === 'function') window.hideElLoading(btn);
                }
            }

            function openCobroModal(button) {
                var cobro = JSON.parse(button.dataset.cobro || '{}');
                var body = document.getElementById('modal-body-cobro');
                var monto = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(cobro.monto || 0);
                var fecha = cobro.fecha_cobro ? new Date(cobro.fecha_cobro).toLocaleDateString('es-CL') : 'No definida';
                var deudor = cobro.deudor_id ? '<a href="/cliente/ficha/' + cobro.deudor_id + '" class="text-decoration-none">' + cobro.deudor + '</a>' : cobro.deudor;
                var acreedor = cobro.acreedor_id ? '<a href="/cliente/ficha/' + cobro.acreedor_id + '" class="text-decoration-none">' + cobro.acreedor + '</a>' : cobro.acreedor;

                body.innerHTML = '<p><b>Tipo de cobro:</b> ' + (cobro.tipo || 'Cobro pendiente') + '</p>'
                    + '<p><b>Deudor:</b> ' + (deudor || 'Sin deudor') + '</p>'
                    + '<p><b>Acreedor:</b> ' + (acreedor || 'Sin acreedor') + '</p>'
                    + '<p><b>Monto:</b> ' + monto + '</p>'
                    + '<p><b>Fecha de pago:</b> ' + fecha + '</p>';

                document.getElementById('btn-registrar').onclick = function () { registrarPago(cobro); };
                var modalEl = document.getElementById('modalCobro');
                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                prepareTerminacionModalStack(modalEl);
                modal.show();
            }

            window.initTerminacionContratoPreview = function (contractId) {
                setTimeout(function () {
                    var preview = document.querySelector('#modalPrincipalBody .terminacion-preview[data-contrato-id="' + contractId + '"]');
                    if (preview) {
                        labelTerminacionTables(preview);
                        recalculate(preview);
                    }
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
                    if (!row) return;
                    removeAdjustment(row, preview);
                }
                if (event.target.classList.contains('terminacion-confirm')) terminateContract(preview, event.target);
            });

            document.addEventListener('click', function (event) {
                var cobroButton = event.target.closest('.btn-cobro');
                if (cobroButton && cobroButton.closest('.terminacion-preview')) openCobroModal(cobroButton);
            });

            document.querySelectorAll('[data-terminacion-stacked-modal="true"]').forEach(function (modalEl) {
                modalEl.addEventListener('shown.bs.modal', function () {
                    applyTerminacionModalStack(modalEl);
                });
                modalEl.addEventListener('hidden.bs.modal', function () {
                    modalEl.style.zIndex = '';
                    restoreTerminacionParentModalState();
                });
            });

            document.addEventListener('hidden.bs.modal', function (event) {
                if (!event.target.matches('[data-terminacion-stacked-modal="true"]')) {
                    restoreTerminacionParentModalState();
                }
            });
        })();
    </script>
@endonce
