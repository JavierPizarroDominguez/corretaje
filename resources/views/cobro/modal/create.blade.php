{{--
    modal/create.blade.php — Formulario para crear Cobro en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo cobro', vista: 'vista-crear-cobro'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-cobro">
                @include('cobro.modal.create')
            </div>
        </div>

    Ficha context (cliente/propiedad detail):
        $fichaContext (bool) — enables restricted type list, hidden date/state,
        CLP formatting on monto, deudor/acreedor from contract participants.
        $participantOptions (Collection) — contract participants for deudor/acreedor.
--}}

<form method="POST" action="/cobro" id="form-modal-create-cobro"
      data-clientes='@json($clienteOptions ?? collect())'
      @if($fichaContext ?? false) data-ficha-context="1"@endif>
    @csrf
    {{-- CUSTOM: cliente_id set by parent view via JS before opening modal --}}
    <input type="hidden" name="cliente_id" id="input-create-cliente-id" value="">

    @if($fichaContext ?? false)
        {{-- Ficha context: _ficha_context flag for server enforcement --}}
        <input type="hidden" name="_ficha_context" value="1">
    @else
        {{-- Generic context: visible date and state fields --}}
        <div class="mb-3">
            <label class="form-label">Fecha Cobro</label>
            <input type="datetime-local" name="fecha_cobro" class="form-control" value="{{ old('fecha_cobro', now()->format('Y-m-d\TH:i')) }}">
            @error('fecha_cobro') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="Pagado" {{ old('estado') === 'Pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="Incompleto" {{ old('estado') === 'Incompleto' ? 'selected' : '' }}>Incompleto</option>
                <option value="Pendiente" {{ old('estado', 'Pendiente') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Vencido" {{ old('estado') === 'Vencido' ? 'selected' : '' }}>Vencido</option>
                <option value="Anulado" {{ old('estado') === 'Anulado' ? 'selected' : '' }}>Anulado</option>
            </select>
            @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" id="create-cobro-tipo"
                onchange="var tipo=this.value; var esServicio=['Luz','Agua','Gas','Gastos comunes'].includes(tipo); var esRepDev=['Reparación','Devolución','Extra'].includes(tipo); var isFicha = getModalElement('form-modal-create-cobro')?.dataset.fichaContext === '1'; var isGenericNoFicha = !isFicha; if (isGenericNoFicha) { document.getElementById('create-detalle-wrapper').classList.toggle('d-none', !esRepDev); document.getElementById('create-monto-wrapper').classList.toggle('d-none', tipo===''); document.getElementById('create-propiedad-wrapper').classList.toggle('d-none', tipo===''); document.getElementById('create-deudor-wrapper').classList.toggle('d-none', tipo===''); document.getElementById('create-acreedor-wrapper').classList.toggle('d-none', tipo==='' || esServicio); window._esServicio = esServicio; } var mc = getModalElement('modal-cliente-id'); var fc = getModalElement('input-create-cliente-id'); if(mc && fc) fc.value = mc.value; if(typeof resolveCobroRelationships === 'function') resolveCobroRelationships();">
            <option value="">— Seleccionar —</option>
            @if($fichaContext ?? false)
                {{-- Ficha context: only manual types --}}
                <option value="Reparación">Reparación</option>
                <option value="Devolución">Devolución</option>
                <option value="Extra">Extra</option>
            @else
                @foreach($tiposCobroDisponibles ?? collect() as $tipoOption)
                    <option value="{{ $tipoOption }}" {{ old('tipo') === $tipoOption ? 'selected' : '' }}>{{ $tipoOption }}</option>
                @endforeach
            @endif
        </select>
        @error('tipo') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    @if($fichaContext ?? false)
        {{-- Ficha context: monto with CLP formatting, required --}}
        <div class="mb-3" id="create-monto-wrapper">
            <label class="form-label">Monto</label>
            {{-- Visible input: cosmetic only (no name), formats as CLP while typing --}}
            <input type="text" inputmode="numeric" class="form-control"
                   id="create-monto-display"
                   value="{{ old('monto') ? '$' . number_format(old('monto'), 0, ',', '.') : '' }}"
                   required
                   oninput="(function(el){ if(typeof window.handleCLPInput==='function') window.handleCLPInput(el); var raw=typeof window.stripCLP==='function'?window.stripCLP(el.value):''; var h=el.closest('.mb-3')?.querySelector('input[name=monto]'); if(h) h.value=raw; })(this);">
            {{-- Hidden input: carries the raw integer for form submission --}}
            <input type="hidden" name="monto" id="create-monto-hidden" value="{{ old('monto') }}">
            @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3" id="create-detalle-wrapper">
            <label class="form-label">Detalle</label>
            <input type="text" name="detalle" class="form-control" value="{{ old('detalle') }}" required>
            @error('detalle') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    @else
        <div class="mb-3 d-none" id="create-monto-wrapper">
            <label class="form-label">Monto</label>
            <input type="number" name="monto" class="form-control" value="{{ old('monto') }}">
            @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3 d-none" id="create-detalle-wrapper">
            <label class="form-label">Detalle</label>
            <input type="text" name="detalle" class="form-control" value="{{ old('detalle') }}">
            @error('detalle') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="mb-3 d-none">
        {{-- CUSTOM: Contrato hidden — auto-resolved via AJAX, no manual selection --}}
        <input type="hidden" name="Contrato_id" id="input-create-contrato-id" value="{{ old('Contrato_id') }}">
    </div>
    <div class="mb-3 d-none">
        {{-- CUSTOM: Servicio hidden — auto-resolved via AJAX for utility tipos --}}
        <input type="hidden" name="Servicio_id" id="input-create-servicio-id" value="{{ old('Servicio_id') }}">
    </div>
    @php
        $propiedadCountActual = $propiedadOptions->count();
        $currentPropiedad = old('Propiedad_id') ?? ($cobro->Propiedad_id ?? null);
        $unicaPropiedad = $propiedadCountActual === 1 ? $propiedadOptions->first() : null;
    @endphp
    <div class="mb-3 d-none" id="create-propiedad-wrapper">
        <label class="form-label">Propiedad</label>
        @if($propiedadCountActual === 1)
            {{-- CUSTOM: Single property — show as read-only text, submit as hidden input --}}
            <div class="form-control-plaintext border rounded px-2 py-1 bg-light">
                {{ $unicaPropiedad->direccion }}
            </div>
            <input type="hidden" name="Propiedad_id" id="input-create-propiedad-select" value="{{ $unicaPropiedad->id }}">
        @else
            <select name="Propiedad_id" class="form-select" id="input-create-propiedad-select" onchange="if(typeof resolveCobroRelationships === 'function') resolveCobroRelationships();">
                <option value="">— Seleccionar —</option>
                @foreach($propiedadOptions as $option)
                    <option value="{{ $option->id }}"
                            {{ $currentPropiedad == $option->id ? 'selected' : '' }}>
                        {{ $option->direccion }}
                    </option>
                @endforeach
            </select>
            @error('Propiedad_id') <span class="text-danger">{{ $message }}</span> @enderror
        @endif
    </div>
    <div class="mb-3 d-none">
        {{-- CUSTOM: Unidad hidden — auto-resolved via AJAX --}}
        <input type="hidden" name="Unidad_id" id="input-create-unidad-id" value="{{ old('Unidad_id') }}">
    </div>

    @if($fichaContext ?? false)
        {{-- Ficha context: deudor/acreedor as required <select> from contract participants --}}
        <div class="mb-3" id="create-deudor-wrapper">
            <label class="form-label">Deudor</label>
            <input type="hidden" name="deudor_Cliente_id" id="input-create-deudor-id" value="{{ old('deudor_Cliente_id') }}">
            <select id="select-deudor" class="form-select" required
                    onchange="document.getElementById('input-create-deudor-id').value = this.value;">
                <option value="">Seleccione</option>
                @if(isset($participantOptions) && $participantOptions->count())
                    @foreach($participantOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('deudor_Cliente_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                @endif
            </select>
            @error('deudor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3" id="create-acreedor-wrapper">
            <label class="form-label">Acreedor</label>
            <input type="hidden" name="acreedor_Cliente_id" id="input-create-acreedor-id" value="{{ old('acreedor_Cliente_id') }}">
            <select id="select-acreedor" class="form-select" required
                    onchange="document.getElementById('input-create-acreedor-id').value = this.value;">
                <option value="">Seleccione</option>
                @if(isset($participantOptions) && $participantOptions->count())
                    @foreach($participantOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('acreedor_Cliente_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                @endif
            </select>
            @error('acreedor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    @else
        <div class="mb-3 d-none" id="create-deudor-wrapper">
            {{-- CUSTOM: Deudor select — auto-resolved via AJAX, editable by user --}}
            <input type="hidden" name="deudor_Cliente_id" id="input-create-deudor-id" value="{{ old('deudor_Cliente_id') }}">
            <label class="form-label">Deudor</label>
            <select id="select-deudor" class="form-select" onchange="document.getElementById('input-create-deudor-id').value = this.value;">
                <option value="">— Seleccionar —</option>
                @if(isset($clienteOptions) && $clienteOptions->count())
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->nombre }}</option>
                    @endforeach
                @endif
                <option value="1">Corredor</option>
            </select>
            @error('deudor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3 d-none" id="create-acreedor-wrapper">
            {{-- CUSTOM: Acreedor select — auto-resolved via AJAX, editable by user --}}
            <input type="hidden" name="acreedor_Cliente_id" id="input-create-acreedor-id" value="{{ old('acreedor_Cliente_id') }}">
            <label class="form-label">Acreedor</label>
            <select id="select-acreedor" class="form-select" onchange="document.getElementById('input-create-acreedor-id').value = this.value;">
                <option value="">— Seleccionar —</option>
                @if(isset($clienteOptions) && $clienteOptions->count())
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->nombre }}</option>
                    @endforeach
                @endif
                <option value="1">Corredor</option>
            </select>
            @error('acreedor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>

@push('scripts')
<script>
    // CUSTOM: Show error in flashModal
    function showCobroError(message) {
        var modalEl = document.getElementById('flashModal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        var header = document.getElementById('flashHeader');
        var title = document.getElementById('flashTitle');
        var body = document.getElementById('flashBody');
        if (header) {
            header.classList.remove('bg-success', 'text-white');
            header.classList.add('bg-danger', 'text-white');
        }
        if (title) title.innerText = 'Error';
        if (body) body.innerText = message;
        modal.show();
    }

    // CUSTOM: All modal element lookups must go through getModalElement()
    // because abrirModal() uses cloneNode() and moves elements to modal body.
    // ================================================================
    function getModalElement(id) {
        // The modal body is #modalPrincipalBody; search there first
        const modalBody = document.getElementById('modalPrincipalBody');
        if (modalBody) {
            const el = modalBody.querySelector('#' + id);
            if (el) return el;
        }
        // Fallback to document (for elements outside modal or before it opens)
        return document.getElementById(id);
    }

    // CUSTOM: Initialize cliente_id from parent context when modal opens
    // ================================================================
    (function() {
        const modalClienteIdInput = getModalElement('modal-cliente-id');
        const formClienteIdInput = getModalElement('input-create-cliente-id');
        if (modalClienteIdInput && formClienteIdInput) {
            formClienteIdInput.value = modalClienteIdInput.value;
        }
    })();

    // CUSTOM: Initialize propiedad_id from parent context when modal opens (propiedad ficha)
    // ================================================================
    (function() {
        const modalPropiedadIdInput = getModalElement('modal-propiedad-id');
        const formPropiedadSelect = getModalElement('input-create-propiedad-select');
        if (modalPropiedadIdInput && formPropiedadSelect) {
            // Set the hidden property input value
            formPropiedadSelect.value = modalPropiedadIdInput.value;
            // Trigger relationship resolution after pre-selection
            if (typeof resolveCobroRelationships === 'function') {
                resolveCobroRelationships();
            }
        }
    })();

    // CUSTOM: Build select options from data attribute inside active modal
    // ==============================================================
    function buildClienteOptions(select) {
        const form = getModalElement('form-modal-create-cobro');
        const clientesData = JSON.parse(form?.dataset.clientes || '[]');
        select.innerHTML = '<option value="">— Seleccionar —</option>';
        clientesData.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            select.appendChild(opt);
        });
        // Always add Corredor
        const corredorOpt = document.createElement('option');
        corredorOpt.value = '1';
        corredorOpt.textContent = 'Corredor';
        select.appendChild(corredorOpt);
    }

    // CUSTOM: Build participant select options from participants array
    // ==============================================================
    function buildParticipantOptions(select, participants) {
        select.innerHTML = '<option value="">Seleccione</option>';
        if (!participants || !participants.length) return;
        participants.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nombre;
            select.appendChild(opt);
        });
    }

    (function() {
        const deudorSelect = getModalElement('select-deudor');
        const acreedorSelect = getModalElement('select-acreedor');
        const isFicha = getModalElement('form-modal-create-cobro')?.dataset.fichaContext === '1';
        if (!isFicha) {
            // Generic: build from clientes data attribute
            if (deudorSelect) buildClienteOptions(deudorSelect);
            if (acreedorSelect) buildClienteOptions(acreedorSelect);
        }
        // Ficha context uses server-rendered options from $participantOptions
    })();

    // CUSTOM: Resolve relationships when tipo or propiedad changes
    // ==============================================================

    const resolveCobroRelationships = async () => {
        const clienteId = getModalElement('input-create-cliente-id')?.value;
        const tipo = getModalElement('create-cobro-tipo')?.value;
        const propiedadId = getModalElement('input-create-propiedad-select')?.value;

        if (!clienteId || !tipo) {
            return;
        }

        const params = new URLSearchParams({
            cliente_id: clienteId,
            tipo: tipo,
        });

        if (propiedadId) {
            params.append('propiedad_id', propiedadId);
        }

        // Get the form or modal body for spinner target
        const modalBody = getModalElement('modalPrincipalBody') || getModalElement('form-modal-create-cobro');
        const form = getModalElement('form-modal-create-cobro');

        // Disable form fields during loading
        if (form) {
            form.querySelectorAll('input, select').forEach(function(el) {
                el.disabled = true;
            });
        }

        if (typeof window.showElLoading === 'function' && modalBody) {
            window.showElLoading(modalBody);
        }

        try {
            const response = await fetch('/api/cobro/resolve-relationships', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString(),
            });

            const result = await response.json();
            console.log('AJAX resolve result:', result);

            if (typeof window.hideElLoading === 'function' && modalBody) {
                window.hideElLoading(modalBody);
            }
            if (form) {
                form.querySelectorAll('input, select').forEach(function(el) {
                    el.disabled = false;
                });
            }

            if (!response.ok) {
                const errorMsg = result?.message || 'Error del servidor al resolver relaciones.';
                showCobroError(errorMsg);
                return;
            }

            if (!result.data) return;

            // Populate hidden inputs
            getModalElement('input-create-contrato-id').value = result.data.contrato_id || '';
            getModalElement('input-create-unidad-id').value = result.data.unidad_id || '';
            getModalElement('input-create-servicio-id').value = result.data.servicio_id || '';
            getModalElement('input-create-deudor-id').value = result.data.deudor_cliente_id || '';
            getModalElement('input-create-acreedor-id').value = result.data.acreedor_cliente_id || '';

            // CUSTOM: auto-fill monto with renta for renta tipos
            if (result.data.renta && (tipo === 'Ingreso Renta Arrendatario' || tipo === 'Egreso Renta Arrendador')) {
                const montoInput = getModalElement('create-monto-wrapper')?.querySelector('input[name="monto"]');
                if (montoInput) montoInput.value = result.data.renta;
            }

            // CUSTOM: in ficha context, rebuild deudor/acreedor from resolved participants
            const isFicha = form?.dataset.fichaContext === '1';
            if (isFicha && result.data.participants) {
                const deudorSelect = getModalElement('select-deudor');
                const acreedorSelect = getModalElement('select-acreedor');
                buildParticipantOptions(deudorSelect, result.data.participants);
                buildParticipantOptions(acreedorSelect, result.data.participants);

                // Preselect if the resolver found defaults
                if (result.data.deudor_cliente_id && deudorSelect) {
                    deudorSelect.value = result.data.deudor_cliente_id;
                }
                if (result.data.acreedor_cliente_id && acreedorSelect) {
                    acreedorSelect.value = result.data.acreedor_cliente_id;
                }
            } else {
                // Generic context: preselect Deudor and Acreedor
                function ensureOption(select, value, text) {
                    if (!value || !select) return;
                    let opt = select.querySelector('option[value="' + value + '"]');
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = text || value;
                        select.appendChild(opt);
                    }
                }

                const deudorSelect = getModalElement('select-deudor');
                const acreedorSelect = getModalElement('select-acreedor');

                if (result.data.deudor_cliente_id) {
                    ensureOption(deudorSelect, result.data.deudor_cliente_id, result.data.deudor_nombre);
                    if (deudorSelect) deudorSelect.value = result.data.deudor_cliente_id;
                }
                if (result.data.acreedor_cliente_id) {
                    ensureOption(acreedorSelect, result.data.acreedor_cliente_id, result.data.acreedor_nombre);
                    if (acreedorSelect) acreedorSelect.value = result.data.acreedor_cliente_id;
                }
            }

            // If multiple contracts, update property select with only those options
            if (result.data.multiple && result.options && result.options.length > 0) {
                const propiedadSelect = getModalElement('input-create-propiedad-select');
                if (propiedadSelect) {
                    propiedadSelect.innerHTML = '<option value="">— Seleccionar propiedad —</option>';
                    result.options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.propiedad_id;
                        option.textContent = opt.direccion + (opt.unidad_nombre ? ' (' + opt.unidad_nombre + ')' : '');
                        propiedadSelect.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error resolving cobro relationships:', error);
            if (typeof window.hideElLoading === 'function' && modalBody) {
                window.hideElLoading(modalBody);
            }
            if (form) {
                form.querySelectorAll('input, select').forEach(function(el) {
                    el.disabled = false;
                });
            }
            showCobroError('Error al resolver relaciones del cobro. Intente nuevamente.');
        }
    };

    // Make resolve function globally available for inline onchange handlers
    window.resolveCobroRelationships = resolveCobroRelationships;
</script>
@endpush
