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
                        onclick="abrirModal({titulo: 'Término de contrato', vista: 'vista-terminar-contrato-{{ $contrato->id }}', size: 'modal-xl'}); window.initTerminacionContratoPreview && window.initTerminacionContratoPreview({{ $contrato->id }});">
                    <i class="ti ti-door-exit"></i> Terminar contrato
                </button>
            </div>

            <div id="vista-terminar-contrato-{{ $contrato->id }}" class="d-none">
                <div class="terminacion-preview" data-contrato-id="{{ $contrato->id }}" data-garantia="{{ (int) $contrato->garantia }}">
                    <h6>Días proporcionales de renta</h6>
                    <p class="text-muted mb-3">Al terminar un contrato, se generará el cobro pendiente proporcional de renta entre el día de pago y la fecha de término del contrato.</p>
                    <table>
                        <tr>
                            <td>Día de pago:</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td>Día de término de contrato:</td>
                            <td>11</td>
                        </tr>
                         <tr>
                            <td>Renta</td>
                            <td>$300.000.-</td>
                        </tr>
                        <tr>
                            <td>Cantidad de días del mes:</td>
                            <td>30</td>
                        </tr>
                        <tr>
                            <td>Valor día proporcional:</td>
                            <td>$10.000.-</td>
                        </tr>
                        <tr>
                            <td>Días proporcionales:</td>
                            <td>6</td>
                        </tr>
                        <tr>
                            <td>Total Días proporcionales:</td>
                            <td>$60.000.-</td>
                        </tr>
                    </table>
                    <h6>Días proporcionales de servicios</h6>
                    <p class="text-muted mb-3">Al mes siguiente, cuando llegue la boleta de los servicios, también se cobrará el proporcional de los días de uso entre el día de pago y la fecha de término del contrato.</p>
                    <h6>Devolución de garantía</h6>
                    <p class="text-muted mb-3">Se generará también el cobro de la Devolución de Garantía, por lo que a partir del término de contrato, tiene 30 días para realizar la transferencia al arrendatario de la garantía que pagó en un comienzo. El pago se realiza del mismo modo que los demás cobros, con la salvedad de que ahí podrá ingresar los descuentos que deben realizarse, como reparaciones, aseo y extras. </p>
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
            function labelTerminacionTables(preview) {
                preview.querySelectorAll('.terminacion-pendientes-table').forEach(function (table) {
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
                        body: JSON.stringify({})
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
                    }
                }, 0);
            };

            document.addEventListener('click', function (event) {
                var preview = event.target.closest('.terminacion-preview');
                if (!preview) return;
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
