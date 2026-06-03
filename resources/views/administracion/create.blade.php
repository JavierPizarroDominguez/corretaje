@extends('layouts.app')

@section('content')
<div class="container" x-data="administracionWizard()">

    {{-- Fixed title (like legacy h1 at line 207) --}}
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="fs-3 mb-1">Agregar administración</h1>
        </div>
    </div>

    {{-- Progress Steps (idéntico al legacy #progress-steps) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div id="progress-steps" class="progress-steps-container">
                <template x-for="(stepInfo, idx) in visibleSteps" :key="idx">
                    <div class="d-flex align-items-center" style="flex:1;">
                        <div class="progress-step text-center"
                             :class="getStepClass(idx)"
                             :style="(getStepClass(idx).includes('completed') || getStepClass(idx).includes('reached')) && !getSkippedSteps().has(idx + 1) ? 'cursor:pointer;' : ''"
                             @click="goToStep(idx + 1)">
                            <div class="step-circle" x-text="getStepNumber(idx)"></div>
                            <div class="step-label" x-text="stepInfo.label"></div>
                        </div>
                        <template x-if="idx < visibleSteps.length - 1">
                            <div class="step-line" :class="getLineClass(idx)"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('administracion.store') }}" id="wizard-form" enctype="multipart/form-data">
        @csrf

        {{-- Hidden date fields (not shown in wizard) --}}
        <input type="hidden" name="fecha_firma" value="{{ old('fecha_firma') }}">
        <input type="hidden" name="fecha_inicio" value="{{ old('fecha_inicio') }}">
        <input type="hidden" name="fecha_termino" value="{{ old('fecha_termino') }}">

        <div class="card">
            <div class="card-body">

                {{-- Dynamic Resumen Panel (like legacy #resumen-wrapper) --}}
                <div class="row mb-4" id="resumen-wrapper" style="display:none;">
                    <div class="col-12">
                        <div class="resumen-card" style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:1rem 1.25rem;">
                            <h6 class="resumen-titulo" style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;margin-bottom:.6rem;">Resumen de la administración</h6>
                            <table id="resumen-administracion" class="resumen-table" style="width:100%;border-collapse:collapse;font-size:.9rem;">
                                <tbody>
                                    <tr data-key="arrendador" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Arrendador</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="arrendatario" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Arrendatario</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="propiedad" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Propiedad</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="administracion" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Administración</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="renta" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Renta</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="dia-pago-renta" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Día de pago</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="comision-inicial" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Comisión inicial</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="egreso" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Egreso / Comisión mensual</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="garantia" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Garantía</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                    <tr data-key="servicios" style="display:none;border-bottom:1px solid #e9ecef;">
                                        <td class="resumen-label" style="width:40%;padding:5px 8px 5px 0;color:#6c757d;font-weight:500;white-space:nowrap;">Servicios</td>
                                        <td class="resumen-value" style="padding:5px 0;color:#212529;font-weight:600;"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Dynamic step title + subtitle (like legacy #titulo / #subtitulo) --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <h1 class="fs-3 mb-1" id="titulo" x-text="stepMeta[Math.min(step - 1, stepMeta.length - 1)].titulo">Arrendador</h1>
                        <p id="subtitulo" x-html="stepMeta[Math.min(step - 1, stepMeta.length - 1)].sub"></p>
                    </div>
                </div>

                {{-- Step 1: Arrendador --}}
                <div x-show="step === 1" style="display:none;">
                    @include('administracion.partials.step-01-arrendador')
                </div>

                {{-- Step 2: Arrendatario --}}
                <div x-show="step === 2" style="display:none;">
                    @include('administracion.partials.step-02-arrendatario')
                </div>

                {{-- Step 3: Propiedad --}}
                <div x-show="step === 3" style="display:none;">
                    @include('administracion.partials.step-03-propiedad')
                </div>

                {{-- Step 4: Administración --}}
                <div x-show="step === 4" style="display:none;">
                    @include('administracion.partials.step-04-contrato')
                </div>

                {{-- Step 5: Comisión Inicial (conditional on !sin_administracion) --}}
                <div x-show="step === 5 && !sin_administracion" style="display:none;">
                    @include('administracion.partials.step-05-comision')
                </div>

                {{-- Step 6: Egreso (conditional on !sin_administracion) --}}
                <div x-show="step === 6 && !sin_administracion" style="display:none;">
                    @include('administracion.partials.step-06-egreso')
                </div>

                {{-- Step 7: Garantía (conditional on !sin_administracion) --}}
                <div x-show="step === 7 && !sin_administracion" style="display:none;">
                    @include('administracion.partials.step-07-garantia')
                </div>

                {{-- Step 8: Servicios â€” LAST STEP with submit --}}
                <div x-show="step === 8" style="display:none;">
                    @include('administracion.partials.step-08-servicios')
                </div>

                {{-- Navigation (legacy has no bottom nav â€” all via "Añadir" buttons + timeline click) --}}
                <div class="d-flex gap-2 mt-4 pt-3 border-top" x-show="step < 8">
                    <button type="button" class="btn btn-outline-secondary"
                            x-show="step > 1"
                            @click="goToStep(step - 1)">Anterior
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* â”€â”€ Progress Steps (idéntico al legacy) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .progress-steps-container {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0;
        padding: 1rem 0 0.5rem;
    }
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 64px;
        transition: opacity 0.2s;
    }
    .progress-step.completed { opacity: 1; }
    .progress-step.completed:hover .step-circle {
        background: #0d6efd;
        transform: scale(1.1);
    }
    .step-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        border: 2px solid #dee2e6;
        background: #f8f9fa;
        color: #adb5bd;
        transition: all 0.25s ease;
    }
    .progress-step.completed .step-circle {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }
    .progress-step.current .step-circle {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
    }
    .step-label {
        font-size: 0.65rem;
        text-align: center;
        margin-top: 4px;
        color: #adb5bd;
        max-width: 64px;
        line-height: 1.2;
    }
    .progress-step.completed .step-label { color: #198754; }
    .progress-step.current .step-label { color: #0d6efd; font-weight: 600; }
    .progress-step.reached .step-circle {
        background: #fff;
        border-color: #198754;
        color: #198754;
    }
    .progress-step.reached .step-label { color: #198754; font-weight: 600; }
    .step-line {
        flex: 1;
        height: 2px;
        background: #dee2e6;
        min-width: 16px;
        margin-bottom: 20px;
        transition: background 0.25s;
    }
    .step-line.done { background: #198754; }
    .progress-step.skipped .step-circle {
        background: #e9ecef;
        border-color: #ced4da;
        color: #adb5bd;
        text-decoration: line-through;
    }
    .progress-step.skipped .step-label {
        color: #ced4da;
        text-decoration: line-through;
    }
    .step-line.skipped {
        background: repeating-linear-gradient(90deg, #ced4da 0, #ced4da 4px, transparent 4px, transparent 8px);
    }
    /* â”€â”€ Resumen Table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .resumen-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem 1.25rem;
    }
    .resumen-titulo {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        margin-bottom: 0.6rem;
    }
    .resumen-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .resumen-table tr { border-bottom: 1px solid #e9ecef; }
    .resumen-table tr:last-child { border-bottom: none; }
    .resumen-label {
        width: 40%;
        padding: 5px 8px 5px 0;
        color: #6c757d;
        font-weight: 500;
        white-space: nowrap;
    }
    .resumen-value {
        padding: 5px 0;
        color: #212529;
        font-weight: 600;
    }
    .badge-servicio {
        display: block;
        background: #e7f1ff;
        color: #0d6efd;
        border-radius: 4px;
        padding: 3px 7px;
        font-size: 0.82rem;
        margin: 3px 0;
        width: fit-content;
    }
</style>
@endpush

@push('scripts')
{{-- Alpine.js CDN --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
// Uses global buscador() from public/js/buscador.js

var SERVICIOS_BASE = ['Luz', 'Agua', 'Gas', 'Gastos Comunes'];

function getServiciosDisponibles() {
    var alpineEl = document.querySelector('[x-data]');
    if (!alpineEl || !alpineEl._x_dataStack) return SERVICIOS_BASE.slice();
    var wizard = alpineEl._x_dataStack[0];
    var addedTypes = (wizard.servicios || []).map(function(s) { return s.tipo; });
    return SERVICIOS_BASE.filter(function(t) { return addedTypes.indexOf(t) === -1; });
}

function renderServicioSelect() {
    var selectEl = document.getElementById('servicioSelect');
    if (!selectEl) return;
    var available = getServiciosDisponibles();
    selectEl.innerHTML = '<option value="">Seleccione un servicio</option>';
    available.forEach(function(t) {
        var opt = document.createElement('option');
        opt.value = t;
        opt.textContent = t;
        selectEl.appendChild(opt);
    });
}

function actualizarVisibilidadBotonServicio() {
    var alpineEl = document.querySelector('[x-data]');
    if (!alpineEl || !alpineEl._x_dataStack) return;
    var wizard = alpineEl._x_dataStack[0];
    var count = (wizard.servicios || []).filter(function(s) { return s.tipo !== 'Sin servicios'; }).length;
    var btn = document.getElementById('btnToggleServicio');
    var inputs = document.getElementById('inputsServicio');
    if (btn) btn.style.display = count >= 4 ? 'none' : '';
    if (inputs && count >= 4) inputs.style.display = 'none';
}

function loadPropiedadesPorArrendador(arrendadorId) {
    var selectEl = document.getElementById('propiedadSelect');
    var inputEl  = document.getElementById('nuevaPropiedadInput');

    if (!selectEl || !inputEl) return;

    selectEl.style.display = 'none';
    inputEl.style.display = 'none';
    selectEl.style.display = 'block';

    if (typeof window.showElLoading === 'function') {
        window.showElLoading(selectEl);
    }

    fetch('/api/propiedades/por-arrendador/' + encodeURIComponent(arrendadorId))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(selectEl);
            }
            selectEl.innerHTML = '<option value="">Seleccionar propiedad...</option>';
            if (!data.length) {
                var emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = 'Sin propiedades registradas';
                selectEl.appendChild(emptyOpt);
            } else {
                data.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.direccion + (item.unidad_id ? ' (con unidad)' : '');
                    selectEl.appendChild(opt);
                });
            }
            var nuevaOpt = document.createElement('option');
            nuevaOpt.value = 'nueva';
            nuevaOpt.textContent = '\u2795 Agregar nueva propiedad';
            selectEl.appendChild(nuevaOpt);
        })
        .catch(function() {
            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(selectEl);
            }
            selectEl.style.display = 'none';
            inputEl.style.display = 'block';
        });
}

function administracionWizard() {
    return {
        step: 1,
        maxReachedStep: 1,
        sin_administracion: {{ old('sin_administracion', 'false') }},
        servicios: [],

        stepMeta: [
            { titulo: 'Arrendador', sub: 'Escriba el nombre del arrendador. Si ya existe en la base de datos, seleccione el cliente sugerido, sino se añadirá el nuevo.' },
            { titulo: 'Arrendatario', sub: 'Escriba el nombre del arrendatario.' },
            { titulo: 'Propiedad', sub: 'Seleccione o ingrese la propiedad correspondiente al arrendador.' },
            { titulo: 'Administración', sub: 'Ingrese el monto de renta y el día de pago que generarán los cobros automáticamente cada mes. Active la casilla si el contrato no incluye administración para no generar cobros.' },
            { titulo: 'Comisión Inicial', sub: 'Ingrese el monto de la comisión inicial, o marque la casilla para no generarla.' },
            { titulo: 'Transferencia al Arrendador y Comisión Mensual', sub: 'Ingrese los montos para generar los cobros automáticamente cada mes' },
            { titulo: 'Garantía', sub: 'Ingrese el monto de garantía o marque la casilla para no cobrarla.' },
            { titulo: 'Servicios', sub: 'Configure los servicios asociados a la propiedad.' }
        ],

        visibleSteps: [
            { label: 'Arrendador' },
            { label: 'Arrendatario' },
            { label: 'Propiedad' },
            { label: 'Administración' },
            { label: 'Comisión Inicial' },
            { label: 'Transferencia' },
            { label: 'Garantía' },
            { label: 'Servicios' }
        ],

        getSkippedSteps: function() {
            var skipped = new Set();
            if (this.sin_administracion) {
                skipped.add(5); // comision (step 5)
                skipped.add(6); // egreso (step 6)
                skipped.add(7); // garantia (step 7)
            }
            return skipped;
        },

        getStepClass: function(idx) {
            var stepNum = idx + 1;
            var skipped = this.getSkippedSteps();
            var isSkipped = skipped.has(stepNum);
            var isCompleted = !isSkipped && stepNum < this.maxReachedStep;
            var isReached = !isSkipped && stepNum === this.maxReachedStep;
            var isCurrent = stepNum === this.step;
            var classes = [];
            if (isCompleted) classes.push('completed');
            if (isReached) classes.push('reached');
            if (isCurrent) classes.push('current');
            if (isSkipped) classes.push('skipped');
            return classes.join(' ');
        },

        getStepNumber: function(idx) {
            var stepNum = idx + 1;
            var skipped = this.getSkippedSteps();
            var isSkipped = skipped.has(stepNum);
            var isCompleted = !isSkipped && stepNum < this.maxReachedStep;
            if (isSkipped) return '\u2013';
            if (isCompleted) return '\u2713';
            return stepNum;
        },

        getLineClass: function(idx) {
            var stepNum = idx + 1;
            var nextStep = stepNum + 1;
            var skipped = this.getSkippedSteps();
            var isSkipped = skipped.has(stepNum);
            var thisIsCompleted = !skipped.has(stepNum) && stepNum < this.maxReachedStep;
            var nextIsCompleted = !skipped.has(nextStep) && nextStep < this.maxReachedStep;
            var classes = [];
            if (thisIsCompleted && nextIsCompleted) classes.push('done');
            if (isSkipped) classes.push('skipped');
            return classes.join(' ');
        },

        goToStep: async function(n) {
            if (n === this.step) return;
            // Moving backward from the maxReachedStep is always free
            if (n < this.step && this.step === this.maxReachedStep) {
                if (n <= this.maxReachedStep && !this.getSkippedSteps().has(n)) {
                    this.step = n;
                }
                return;
            }
            // For any other movement, validate current step first
            if (typeof window.validateStep === 'function' && !await window.validateStep(this.step)) return;
            if (n <= this.maxReachedStep && !this.getSkippedSteps().has(n)) {
                this.step = n;
            }
        },

        nextStep: async function() {
            if (typeof window.validateStep === 'function' && !await window.validateStep(this.step)) return;

            // Use global jumpOrAdvance() for consistent navigation
            if (typeof window.jumpOrAdvance === 'function') {
                window.jumpOrAdvance();
            }
        }
    }
}

// Sanitize numeric inputs: strip leading zeros (e.g., "00123" -> "123")
function sanitizeNumericInput(input) {
    if (!input) return;
    var raw = input.value;
    if (!raw) return;
    // Remove non-numeric chars, then strip leading zeros (keep at least one if all zeros)
    var cleaned = raw.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
    if (cleaned !== raw) {
        input.value = cleaned;
    }
}

// Attach to all number inputs in the wizard form
function attachNumericSanitization() {
    var numericInputs = document.querySelectorAll('#wizard-form input[type="number"]');
    numericInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            sanitizeNumericInput(this);
        });
        input.addEventListener('input', function() {
            // Optional: real-time sanitize on input (may be annoying while typing)
            // Uncomment if desired:
            // sanitizeNumericInput(this);
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Arrendador autocomplete (step 1) â€” uses global buscador() from public/js/buscador.js
    buscador({
        input:    '#input-arrendador',
        list:     '#lista-arrendador',
        tipo:     'cliente',
        onSelect: function(item) {
            document.getElementById('input-arrendador').value = item.texto;
            document.getElementById('hidden-arrendador-id').value = item.id;
            loadPropiedadesPorArrendador(item.id);
        }
    });

    // Arrendatario autocomplete (step 2) â€” uses global buscador() from public/js/buscador.js
    buscador({
        input:    '#input-arrendatario',
        list:     '#lista-arrendatario',
        tipo:     'cliente',
        onSelect: function(item) {
            document.getElementById('input-arrendatario').value = item.texto;
            document.getElementById('hidden-arrendatario-id').value = item.id;
        }
    });

    // Propiedad select change - show/hide nueva input, set hidden fields
    var propiedadSelect = document.getElementById('propiedadSelect');
    if (propiedadSelect) {
        propiedadSelect.addEventListener('change', function() {
            var nuevaPropiedadInput = document.getElementById('nuevaPropiedadInput');
            var hiddenPropiedadId = document.getElementById('hidden-propiedad-id');
            var hiddenDireccion = document.getElementById('nuevaPropiedadInput');
            if (this.value === 'nueva') {
                if (nuevaPropiedadInput) nuevaPropiedadInput.style.display = 'block';
                if (hiddenPropiedadId) hiddenPropiedadId.value = '';
                if (hiddenDireccion) hiddenDireccion.value = '';
            } else if (this.value) {
                if (nuevaPropiedadInput) nuevaPropiedadInput.style.display = 'none';
                if (hiddenPropiedadId) hiddenPropiedadId.value = this.value;
                if (hiddenDireccion) hiddenDireccion.value = this.options[this.selectedIndex].text.split(' (')[0];
            } else {
                if (nuevaPropiedadInput) nuevaPropiedadInput.style.display = 'none';
                if (hiddenPropiedadId) hiddenPropiedadId.value = '';
                if (hiddenDireccion) hiddenDireccion.value = '';
            }
        });
    }

    // Snapshot helpers for sinAdministracion restore
    var sinAdminSnapshot = null;

    function getRentaNumero() {
        var rentaInput = document.querySelector('[name="renta"]');
        return parseInt(rentaInput ? rentaInput.value : 0) || 0;
    }

    // Sync sin_administracion checkbox with Alpine + snapshot
    var sinAdminEl = document.getElementById('sinAdministracion');
    var rentaInput = document.querySelector('[name="renta"]');
    var diaPagoInput = document.querySelector('[name="dia_pago"]');
    if (sinAdminEl) {
        sinAdminEl.addEventListener('change', function() {
            var alpineEl = document.querySelector('[x-data]');
            var wizard = (alpineEl && alpineEl._x_dataStack) ? alpineEl._x_dataStack[0] : null;

            if (this.checked) {
                if (wizard) {
                    sinAdminSnapshot = {
                        egreso: document.querySelector('[name="egreso_renta"]') ? document.querySelector('[name="egreso_renta"]').value : '',
                        comision_mensual: document.querySelector('[name="comision_mensual"]') ? document.querySelector('[name="comision_mensual"]').value : '',
                        garantia: document.querySelector('[name="garantia"]') ? document.querySelector('[name="garantia"]').value : '',
                        servicios: wizard.servicios ? wizard.servicios.slice() : []
                    };
                    wizard.servicios = [];
                }
                if (rentaInput) { rentaInput.disabled = true; rentaInput.value = ''; }
                if (diaPagoInput) { diaPagoInput.disabled = true; diaPagoInput.value = ''; }
            } else {
                if (wizard && sinAdminSnapshot) {
                    var egresoInp = document.querySelector('[name="egreso_renta"]');
                    var comisionInp = document.querySelector('[name="comision_mensual"]');
                    var garantiaInp = document.querySelector('[name="garantia"]');
                    if (egresoInp) egresoInp.value = sinAdminSnapshot.egreso;
                    if (comisionInp) comisionInp.value = sinAdminSnapshot.comision_mensual;
                    if (garantiaInp) garantiaInp.value = sinAdminSnapshot.garantia;
                    wizard.servicios = sinAdminSnapshot.servicios;
                    sinAdminSnapshot = null;
                }
                if (rentaInput) rentaInput.disabled = false;
                if (diaPagoInput) diaPagoInput.disabled = false;
            }
            if (wizard) {
                wizard.sin_administracion = this.checked;
            }
            updateResumen();
        });
    }

    // Sync propiedadCorredor checkbox
    var propiedadCorredor = document.getElementById('propiedadCorredor');
    var arrendadorInput = document.getElementById('input-arrendador');
    var hiddenArrendadorId = document.getElementById('hidden-arrendador-id');
    var btnAddArrendador = document.getElementById('btnAddArrendador');
    if (propiedadCorredor && arrendadorInput) {
        propiedadCorredor.addEventListener('change', function() {
            var alpineEl = document.querySelector('[x-data]');
            var wizard = (alpineEl && alpineEl._x_dataStack) ? alpineEl._x_dataStack[0] : null;
            if (this.checked) {
                arrendadorInput.value = 'Corredor';
                arrendadorInput.disabled = true;
                if (hiddenArrendadorId) hiddenArrendadorId.value = '1';
                if (btnAddArrendador) btnAddArrendador.disabled = true;
                if (wizard) {
                    wizard.step = 2;
                    if (wizard.maxReachedStep < 2) wizard.maxReachedStep = 2;
                }
            } else {
                arrendadorInput.disabled = false;
                arrendadorInput.value = '';
                if (hiddenArrendadorId) hiddenArrendadorId.value = '';
                if (btnAddArrendador) btnAddArrendador.disabled = false;
                if (wizard) wizard.step = 1;
            }
            updateResumen();
        });
    }

    // Sync noComisionInicial checkbox behavior + cross-logic with cobrarArrendador/cobrarArrendatario
    var noComisionInicial = document.getElementById('noComisionInicial');
    var comisionMontoInput = document.getElementById('comisionMontoInput');
    var cobrarArrendador = document.getElementById('cobrarArrendador');
    var cobrarArrendatario = document.getElementById('cobrarArrendatario');

    function checkAutoNoComision() {
        if (!cobrarArrendador || !cobrarArrendatario || !noComisionInicial) return;
        if (!cobrarArrendador.checked && !cobrarArrendatario.checked && !noComisionInicial.checked) {
            noComisionInicial.checked = true;
            comisionMontoInput.disabled = true;
            cobrarArrendador.disabled = true;
            cobrarArrendatario.disabled = true;
        }
    }

    if (noComisionInicial && comisionMontoInput) {
        noComisionInicial.addEventListener('change', function() {
            comisionMontoInput.disabled = this.checked;
            if (cobrarArrendador) cobrarArrendador.disabled = this.checked;
            if (cobrarArrendatario) cobrarArrendatario.disabled = this.checked;
            if (this.checked) {
                if (cobrarArrendador) cobrarArrendador.checked = false;
                if (cobrarArrendatario) cobrarArrendatario.checked = false;
            } else {
                if (cobrarArrendador) cobrarArrendador.checked = true;
                if (cobrarArrendatario) cobrarArrendatario.checked = true;
            }
        });
    }

    if (cobrarArrendador) {
        cobrarArrendador.addEventListener('change', function() {
            if (noComisionInicial.checked) {
                noComisionInicial.checked = false;
                comisionMontoInput.disabled = false;
                cobrarArrendador.disabled = false;
                if (cobrarArrendatario) cobrarArrendatario.disabled = false;
            }
            checkAutoNoComision();
        });
    }
    if (cobrarArrendatario) {
        cobrarArrendatario.addEventListener('change', function() {
            if (noComisionInicial.checked) {
                noComisionInicial.checked = false;
                comisionMontoInput.disabled = false;
                if (cobrarArrendador) cobrarArrendador.disabled = false;
                cobrarArrendatario.disabled = false;
            }
            checkAutoNoComision();
        });
    }

    // Sync noGarantia checkbox behavior
    var noGarantia = document.getElementById('noGarantia');
    var garantiaInput = document.getElementById('garantiaInput');
    if (noGarantia && garantiaInput) {
        noGarantia.addEventListener('change', function() {
            garantiaInput.disabled = this.checked;
            if (this.checked) garantiaInput.value = '';
        });
    }

    // Sync noComisionMensual checkbox behavior
    var noComisionMensual = document.getElementById('noComisionMensual');
    var comisionMensualInput = document.getElementById('comisionMensualInput');
    var egresoRentaInput = document.getElementById('egresoRentaInput');

    if (noComisionMensual && comisionMensualInput && egresoRentaInput) {
        noComisionMensual.addEventListener('change', function() {
            var renta = getRentaNumero();
            comisionMensualInput.disabled = this.checked;
            egresoRentaInput.disabled = this.checked;
            if (this.checked) {
                comisionMensualInput.value = 0;
                egresoRentaInput.value = renta;
            } else {
                egresoRentaInput.value = renta > 0 ? renta : '';
                comisionMensualInput.value = 0;
            }
        });

        egresoRentaInput.addEventListener('input', function() {
            if (noComisionMensual.checked) return;
            var renta = getRentaNumero();
            var egreso = parseInt(this.value) || 0;
            // egreso capped at renta, but must be at least half of renta (comision <= egreso)
            egreso = Math.min(egreso, renta);
            egreso = Math.max(egreso, Math.ceil(renta / 2));
            this.value = egreso;
            comisionMensualInput.value = renta - egreso;
        });

        comisionMensualInput.addEventListener('input', function() {
            if (noComisionMensual.checked) return;
            var renta = getRentaNumero();
            var comision = parseInt(this.value) || 0;
            // comision cannot exceed egreso, so at most half of renta
            var maxComision = Math.floor(renta / 2);
            comision = Math.max(0, Math.min(comision, maxComision));
            this.value = comision;
            egresoRentaInput.value = renta - comision;
        });
    }

    // Servicios dynamic list
    var btnToggleServicio = document.getElementById('btnToggleServicio');
    var inputsServicio = document.getElementById('inputsServicio');
    var servicioSelect = document.getElementById('servicioSelect');
    var servicioDiaPagoInput = document.getElementById('servicioDiaPagoInput');
    var servicioMontoCheck = document.getElementById('servicioMontoFijoCheck');
    var servicioMontoInput = document.getElementById('servicioMontoInput');
    var btnConfirmarServicio = document.getElementById('btnConfirmarServicio');
    var serviciosList = document.getElementById('serviciosList');

    if (btnToggleServicio) {
        btnToggleServicio.addEventListener('click', function() {
            btnToggleServicio.style.display = 'none';
            inputsServicio.style.display = 'block';
        });
    }

    if (servicioMontoCheck && servicioMontoInput) {
        servicioMontoCheck.addEventListener('change', function() {
            var grupoMonto = document.getElementById('grupo-monto');
            if (grupoMonto) {
                grupoMonto.style.display = this.checked ? 'block' : 'none';
            }
            servicioMontoInput.style.display = this.checked ? 'block' : 'none';
            servicioMontoInput.disabled = !this.checked;
            if (!this.checked) servicioMontoInput.value = '';
        });
    }

    if (btnConfirmarServicio) {
        btnConfirmarServicio.addEventListener('click', function(e) {
            e.preventDefault();
            var tipo = servicioSelect.value;
            if (!tipo) { showWizardError('Seleccione un servicio.'); return; }
            var dia = servicioDiaPagoInput.value.trim();
            if (!dia) { showWizardError('Ingrese el día de pago.'); return; }
            var monto = null;
            if (servicioMontoCheck.checked) {
                var montoVal = servicioMontoInput.value.trim();
                if (!montoVal) { showWizardError('Ingrese el monto fijo.'); return; }
                monto = montoVal;
            }

            var alpineEl = document.querySelector('[x-data]');
            if (alpineEl && alpineEl._x_dataStack) {
                var wizard = alpineEl._x_dataStack[0];
                var texto = tipo + ' (Día ' + dia + ')' + (monto ? ' - $' + Number(monto).toLocaleString() : '');
                var serv = { tipo: tipo, dia: dia, monto: monto, texto: texto };
                if (dia < 1 || dia > 31) { serv.dayOutOfRange = true; }
                wizard.servicios.push(serv);

                inputsServicio.style.display = 'none';
                btnToggleServicio.style.display = 'block';
                servicioSelect.value = '';
                servicioDiaPagoInput.value = '';
                servicioMontoCheck.checked = false;
                servicioMontoInput.value = '';
                servicioMontoInput.style.display = 'none';
                document.getElementById('grupo-monto').style.display = 'none';

                renderServiciosList(wizard.servicios);
                updateResumen();
                renderServicioSelect();
                actualizarVisibilidadBotonServicio();
            }
        });
    }

    function renderServiciosList(servicios) {
        if (!serviciosList) return;
        serviciosList.innerHTML = '';
        servicios.filter(function(s) { return s.tipo !== 'Sin servicios'; }).forEach(function(serv) {
            var div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center mb-1';
            div.innerHTML = '<span>' + serv.texto + '</span><button type="button" class="btn btn-sm btn-danger">Eliminar</button>';
            div.querySelector('button').addEventListener('click', function() {
                var alpineEl = document.querySelector('[x-data]');
                if (alpineEl && alpineEl._x_dataStack) {
                    var wizard = alpineEl._x_dataStack[0];
                    wizard.servicios = wizard.servicios.filter(function(s) { return s !== serv; });
                    renderServiciosList(wizard.servicios);
updateResumen(); renderServicioSelect(); actualizarVisibilidadBotonServicio();
                }
            });
            serviciosList.appendChild(div);
        });
    }

    // Helper: if editing a past step, jump back to maxReachedStep; else advance normally
    function aplicarSaltos(wizard) {
        if (wizard.sin_administracion) {
            if (wizard.step >= 5 && wizard.step <= 7) wizard.step = 8;
        }
    }

    function jumpOrAdvance() {
        var alpineEl = document.querySelector('[x-data]');
        if (!alpineEl || !alpineEl._x_dataStack) return;
        var wizard = alpineEl._x_dataStack[0];

        if (wizard.step < wizard.maxReachedStep) {
            // We're editing a past step â€” jump back to where we were
            wizard.step = wizard.maxReachedStep;
            aplicarSaltos(wizard);
        } else {
            // Normal advance
            wizard.step++;
            if (wizard.step > wizard.maxReachedStep) {
                wizard.maxReachedStep = wizard.step;
            }
            aplicarSaltos(wizard);

            // Auto-fill egreso when entering step 6 (Egreso / Transferencia)
            if (wizard.step === 6) {
                var rentaInput = document.querySelector('[name="renta"]');
                var egresoInput = document.getElementById('egresoRentaInput');
                var comisionMensualInput = document.getElementById('comisionMensualInput');
                var noComisionMensualCheck = document.getElementById('noComisionMensual');
                if (rentaInput && egresoInput) {
                    egresoInput.value = rentaInput.value || '';
                }
                if (comisionMensualInput && !noComisionMensualCheck.checked) {
                    comisionMensualInput.value = 0;
                }
            }
        }
        updateResumen();
    }

    var btnAddArrendador = document.getElementById('btnAddArrendador');
    var btnAddArrendatario = document.getElementById('btnAddArrendatario');
    var btnAddPropiedad = document.getElementById('btnAddPropiedad');

    if (btnAddArrendador) {
        btnAddArrendador.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    if (btnAddArrendatario) {
        btnAddArrendatario.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    if (btnAddPropiedad) {
        btnAddPropiedad.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    var btnAddAdmin = document.getElementById('btnAddAdmin');
    if (btnAddAdmin) {
        btnAddAdmin.addEventListener('click', function(e) {
            e.preventDefault();
            var sinAdmin = document.getElementById('sinAdministracion').checked;
            var alpineEl = document.querySelector('[x-data]');
            if (!alpineEl || !alpineEl._x_dataStack) return;
            var wizard = alpineEl._x_dataStack[0];
            if (sinAdmin) {
                // Skip validation when "sin administración" is checked
                wizard.step = 8;
                if (wizard.step > wizard.maxReachedStep) wizard.maxReachedStep = wizard.step;
                updateResumen();
            } else {
                // Use nextStep to enforce validation
                callWizardNextStep();
            }
        });
    }

    // Show validation errors in the same flashModal used by the app
    function showWizardError(message) {
        try {
            var modalEl = document.getElementById('flashModal');
            if (!modalEl) { throw new Error('flashModal not found'); }
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) { throw new Error('bootstrap not loaded'); }
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (!modal) { modal = new bootstrap.Modal(modalEl); }
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
        } catch (e) {
            console.error('showWizardError failed:', e);
            // Fallback: do NOT use alert() per AGENTS.md convention.
            // If bootstrap modal fails, the error is logged to console.
        }
    }

    async function validateNoContratoVigente() {
        var propiedadSelect = document.getElementById('propiedadSelect');
        var stepPropiedad = document.getElementById('step-propiedad');
        var btnAddPropiedad = document.getElementById('btnAddPropiedad');

        if (!propiedadSelect || !propiedadSelect.value || propiedadSelect.value === 'nueva') {
            return true;
        }

        var propiedadId = propiedadSelect.value;

        if (stepPropiedad && typeof window.showElLoading === 'function') {
            window.showElLoading(stepPropiedad);
        }
        if (btnAddPropiedad) btnAddPropiedad.disabled = true;

        try {
            var response = await fetch('/api/propiedades/' + encodeURIComponent(propiedadId) + '/contrato-vigente');
            if (!response.ok) {
                console.error('validateNoContratoVigente: HTTP ' + response.status);
                return true; // fail-open on server error
            }
            var data = await response.json();
            if (data.has_contrato_vigente) {
                var msg = 'La propiedad ';
                if (data.unidad_nombre) {
                    msg += '"' + data.unidad_nombre + '" ';
                }
                msg += 'ya tiene un contrato vigente. No se puede agregar otra administración para esta propiedad.';
                showWizardError(msg);
                return false;
            }
            return true;
        } catch (e) {
            console.error('validateNoContratoVigente: network error', e);
            return true; // fail-open on network error
        } finally {
            if (stepPropiedad && typeof window.hideElLoading === 'function') {
                window.hideElLoading(stepPropiedad);
            }
            if (btnAddPropiedad) btnAddPropiedad.disabled = false;
        }
    }

    async function checkDireccionUnica(direccion) {
        var stepPropiedad = document.getElementById('step-propiedad');
        if (stepPropiedad && typeof window.showElLoading === 'function') {
            window.showElLoading(stepPropiedad);
        }
        try {
            var response = await fetch('/api/propiedades/direccion-check?q=' + encodeURIComponent(direccion));
            if (!response.ok) {
                console.error('checkDireccionUnica: HTTP ' + response.status);
                return true;
            }
            var data = await response.json();
            if (data.exists && data.has_contrato_vigente) {
                var msg = 'La direccion "' + direccion + '" ya existe con un contrato vigente';
                if (data.unidad_nombre) {
                    msg += ' en la unidad "' + data.unidad_nombre + '"';
                }
                msg += '. No se puede crear una nueva administracion para esta propiedad.';
                showWizardError(msg);
                return false;
            }
            if (data.exists && !data.has_contrato_vigente) {
                showWizardError('La direccion "' + direccion + '" ya existe en el sistema. Seleccione la propiedad existente del listado.');
                return false;
            }
            return true;
        } catch (e) {
            console.error('checkDireccionUnica: network error', e);
            return true;
        } finally {
            if (stepPropiedad && typeof window.hideElLoading === 'function') {
                window.hideElLoading(stepPropiedad);
            }
        }
    }

    function normalizeName(name) {
        if (!name) return '';
        return name.toLowerCase()
            .replace(/\s+/g, ' ')
            .replace(/^\s+|\s+$/g, '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function validateArrendatarioDiferenteArrendador() {
        var arrendadorInput = document.getElementById('input-arrendador');
        var arrendatarioInput = document.getElementById('input-arrendatario');
        var hiddenArrendadorId = document.getElementById('hidden-arrendador-id');
        var hiddenArrendatarioId = document.getElementById('hidden-arrendatario-id');

        if (!arrendadorInput || !arrendatarioInput) return true;

        // Check by ID first
        if (hiddenArrendadorId && hiddenArrendatarioId &&
            hiddenArrendadorId.value && hiddenArrendatarioId.value &&
            hiddenArrendadorId.value === hiddenArrendatarioId.value) {
            showWizardError('El arrendador y el arrendatario no pueden ser la misma persona.');
            return false;
        }

        // Check by normalized name as fallback
        var arrendadorNombre = normalizeName(arrendadorInput.value);
        var arrendatarioNombre = normalizeName(arrendatarioInput.value);
        if (arrendadorNombre && arrendatarioNombre && arrendadorNombre === arrendatarioNombre) {
            showWizardError('El arrendador y el arrendatario no pueden ser la misma persona.');
            return false;
        }

        return true;
    }

    async function validateStep(stepNum) {
        if (stepNum === 1) {
            var el = document.querySelector('[name="arrendador_nombre"]');
            if (el && !el.value.trim()) { showWizardError('El nombre del arrendador es obligatorio.'); return false; }
        }
        if (stepNum === 2) {
            var el = document.querySelector('[name="arrendatario_nombre"]');
            if (el && !el.value.trim()) { showWizardError('El nombre del arrendatario es obligatorio.'); return false; }
            if (!validateArrendatarioDiferenteArrendador()) return false;
        }
        if (stepNum === 3) {
            var sel = document.getElementById('propiedadSelect');
            var inp = document.getElementById('nuevaPropiedadInput');
            if (sel && sel.value === 'nueva' && inp && inp.value.trim()) {
                var direccionOk = await checkDireccionUnica(inp.value.trim());
                if (!direccionOk) return false;
            }
            if (sel && sel.value === 'nueva' && inp && !inp.value.trim()) {
                showWizardError('La direccion de la propiedad es obligatoria.'); return false;
            }
            if (sel && !sel.value && inp && !inp.value.trim()) {
                showWizardError('Seleccione o ingrese una propiedad.'); return false;
            }
            if (sel && sel.value && sel.value !== 'nueva') {
                var isValid = await validateNoContratoVigente();
                if (!isValid) return false;
            }
        }
        if (stepNum === 4) {
            if (!document.getElementById('sinAdministracion').checked) {
                var r = document.querySelector('[name="renta"]');
                if (r && !r.value.trim()) { showWizardError('El monto de la renta es obligatorio.'); return false; }
                var d = document.querySelector('[name="dia_pago"]');
                if (d && !d.value.trim()) {                 showWizardError('El día de pago es obligatorio.'); return false; }
            }
        }
        if (stepNum === 5) {
            var sinAdmin = document.getElementById('sinAdministracion').checked;
            if (!sinAdmin) {
                var c = document.querySelector('[name="comision_inicial"]');
                if (c && !c.value.trim() && !document.getElementById('noComisionInicial').checked) {
                    showWizardError('El monto de la comisión inicial es obligatorio.'); return false;
                }
                if (c && c.value.trim() && !document.getElementById('noComisionInicial').checked) {
                    var renta = parseInt(document.querySelector('[name="renta"]')?.value || 0) || 0;
                    var comision = parseInt(c.value) || 0;
                    if (comision > renta) {
                        showWizardError('La comisión inicial no puede ser mayor que la renta ($' + renta.toLocaleString() + ').');
                        return false;
                    }
                }
            }
        }
        if (stepNum === 6) {
            var sinAdmin = document.getElementById('sinAdministracion').checked;
            if (!sinAdmin) {
                var egreso = document.querySelector('[name="egreso_renta"]');
                if (egreso && !egreso.value.trim() && !document.getElementById('noComisionMensual').checked) {
                    showWizardError('El monto de egreso es obligatorio.'); return false;
                }
            }
        }
        if (stepNum === 7) {
            var sinAdmin = document.getElementById('sinAdministracion').checked;
            if (!sinAdmin) {
                var garantia = document.querySelector('[name="garantia"]');
                if (garantia && !garantia.value.trim() && !document.getElementById('noGarantia').checked) {
                    showWizardError('El monto de garantía es obligatorio.'); return false;
                }
            }
        }
        if (stepNum === 8) {
            var alpineEl = document.querySelector('[x-data]');
            if (alpineEl && alpineEl._x_dataStack) {
                var wizard = alpineEl._x_dataStack[0];
                if (wizard.servicios && wizard.servicios.length) {
                    var invalid = wizard.servicios.find(function(s) { return s.dayOutOfRange; });
                    if (invalid) {
                        showWizardError('El servicio "' + invalid.tipo + '" tiene un día de pago inva lido. Debe estar entre 1 y 31.');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    // Make functions accessible to Alpine.js by attaching to window
    window.validateStep = validateStep;
    window.showWizardError = showWizardError;
    window.jumpOrAdvance = jumpOrAdvance;

    async function callWizardNextStep() {
        var alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            var wizard = alpineEl._x_dataStack[0];
            if (typeof wizard.nextStep === 'function') {
                await wizard.nextStep();
            }
        }
    }

    var btnAddComision = document.getElementById('btnAddComision');
    if (btnAddComision) {
        btnAddComision.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    var btnAddEgreso = document.getElementById('btnAddEgreso');
    if (btnAddEgreso) {
        btnAddEgreso.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    var btnAddGarantia = document.getElementById('btnAddGarantia');
    if (btnAddGarantia) {
        btnAddGarantia.addEventListener('click', function(e) {
            e.preventDefault();
            callWizardNextStep();
        });
    }

    // Validate entire form before submission
    var wizardForm = document.getElementById('wizard-form');
    if (wizardForm) {
        wizardForm.addEventListener('submit', async function(e) {
            var alpineEl = document.querySelector('[x-data]');
            if (!alpineEl || !alpineEl._x_dataStack) return;
            var wizard = alpineEl._x_dataStack[0];

            // Validate every step before allowing submission
            for (var stepNum = 1; stepNum <= 8; stepNum++) {
                if (!await validateStep(stepNum)) {
                    wizard.step = stepNum;
                    e.preventDefault();
                    return;
                }
            }
        });
    }

    // Dynamic Resumen Panel
    function updateResumen() {
        var resumenWrapper = document.getElementById('resumen-wrapper');
        if (!resumenWrapper) return;

        var arrendadorInput = document.querySelector('[name="arrendador_nombre"]');
        var arrendatarioInput = document.querySelector('[name="arrendatario_nombre"]');
        var propiedadSelect = document.getElementById('propiedadSelect');
        var nuevaPropiedadInput = document.getElementById('nuevaPropiedadInput');
        var rentaInput = document.querySelector('[name="renta"]');
        var diaPagoInput = document.querySelector('[name="dia_pago"]');
        var comisionInput = document.querySelector('[name="comision_inicial"]');
        var egresoInput = document.querySelector('[name="egreso_renta"]');
        var comisionMensualInput = document.querySelector('[name="comision_mensual"]');
        var garantiaInput = document.querySelector('[name="garantia"]');
        var sinAdminCheck = document.getElementById('sinAdministracion');
        var noComisionCheck = document.getElementById('noComisionInicial');
        var noGarantiaCheck = document.getElementById('noGarantia');
        var noComisionMensualCheck = document.getElementById('noComisionMensual');

        var hasData = false;

        function setRow(key, value, show) {
            var row = document.querySelector('#resumen-wrapper tr[data-key="' + key + '"]');
            if (!row) return;
            if (show && value) {
                row.style.display = '';
                var cell = row.querySelector('.resumen-value');
                if (cell) cell.textContent = value;
                hasData = true;
            } else {
                row.style.display = 'none';
            }
        }

        setRow('arrendador', arrendadorInput ? arrendadorInput.value : '', arrendadorInput && arrendadorInput.value.trim());
        setRow('arrendatario', arrendatarioInput ? arrendatarioInput.value : '', arrendatarioInput && arrendatarioInput.value.trim());

        var propiedadText = '';
        if (propiedadSelect) {
            if (propiedadSelect.value === 'nueva' && nuevaPropiedadInput && nuevaPropiedadInput.value.trim()) {
                propiedadText = nuevaPropiedadInput.value.trim();
            } else if (propiedadSelect.value && propiedadSelect.value !== 'nueva') {
                propiedadText = propiedadSelect.options[propiedadSelect.selectedIndex].text;
            }
        }
        setRow('propiedad', propiedadText, propiedadText);

        var adminText = '';
        if (sinAdminCheck && sinAdminCheck.checked) {
            adminText = 'Sin administración';
        } else if (rentaInput && rentaInput.value) {
            adminText = 'Con administración';
        }
        setRow('administracion', adminText, adminText);

        var rentaText = rentaInput && rentaInput.value ? '$' + Number(rentaInput.value).toLocaleString() : '';
        setRow('renta', rentaText, rentaText);

        var diaPagoText = diaPagoInput && diaPagoInput.value ? 'Día ' + diaPagoInput.value : '';
        setRow('dia-pago-renta', diaPagoText, diaPagoText);

        var comisionText = '';
        if (noComisionCheck && noComisionCheck.checked) {
            comisionText = 'Sin comisión inicial';
        } else if (comisionInput && comisionInput.value) {
            comisionText = '$' + Number(comisionInput.value).toLocaleString();
        }
        setRow('comision-inicial', comisionText, comisionText);

        var egresoText = '';
        if (noComisionMensualCheck && noComisionMensualCheck.checked) {
            egresoText = 'Sin comisión mensual - Egreso: $' + Number(rentaInput ? rentaInput.value : 0).toLocaleString();
        } else if (egresoInput && egresoInput.value) {
            egresoText = 'Egreso: $' + Number(egresoInput.value).toLocaleString() + ' / Comisión: $' + Number(comisionMensualInput ? comisionMensualInput.value : 0).toLocaleString();
        }
        setRow('egreso', egresoText, egresoText);

        var garantiaText = '';
        if (noGarantiaCheck && noGarantiaCheck.checked) {
            garantiaText = 'Sin garantía';
        } else if (garantiaInput && garantiaInput.value) {
            garantiaText = '$' + Number(garantiaInput.value).toLocaleString();
        }
        setRow('garantia', garantiaText, garantiaText);

        var alpineEl = document.querySelector('[x-data]');
        if (alpineEl && alpineEl._x_dataStack) {
            var wizard = alpineEl._x_dataStack[0];
            var serviciosText = '';
            if (wizard.servicios && wizard.servicios.length) {
                var realServicios = wizard.servicios.filter(function(s) { return s.tipo !== 'Sin servicios'; });
                if (realServicios.length) {
                    serviciosText = realServicios.map(function(s) { return s.texto; }).join(', ');
                } else {
                    serviciosText = 'Sin servicios';
                }
            }
            setRow('servicios', serviciosText, serviciosText);
        }

        resumenWrapper.style.display = hasData ? '' : 'none';
    }

    // Attach listeners to all inputs that affect the resumen
    var resumenInputs = document.querySelectorAll('#wizard-form input, #wizard-form select');
    resumenInputs.forEach(function(input) {
        input.addEventListener('change', updateResumen);
        input.addEventListener('input', updateResumen);
    });

    // Initial services dropdown + visibility
    renderServicioSelect();
    actualizarVisibilidadBotonServicio();

    // Initial resumen update
    updateResumen();

    // Sanitize numeric inputs on blur
    attachNumericSanitization();
});
</script>
@endpush


