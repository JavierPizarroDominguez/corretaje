{{-- Step 8: Servicios (idéntico al legacy) — LAST STEP with submit --}}
<div class="row mb-3" id="step-servicio">
    <div class="col-md-8">

        {{-- 1. Lista de servicios agregados --}}
        <div id="serviciosList" class="mb-2"></div>

        {{-- 2. Formulario para nuevo servicio (oculto por defecto) --}}
        <div id="inputsServicio" style="display:none;">
            <div id="grupo-servicio">
                <label>Servicio</label>
                <select id="servicioSelect" class="form-control mb-2">
                    <option value="">Seleccione un servicio</option>
                </select>
            </div>
            <div id="grupo-dia">
                <label>Día de pago</label>
                <input type="number"
                       id="servicioDiaPagoInput"
                       class="form-control mb-2"
                       min="1"
                       max="28">
            </div>
            <div id="grupo-monto-check" class="form-check mb-2">
                <input type="checkbox"
                       id="servicioMontoFijoCheck"
                       class="form-check-input">
                <label class="form-check-label">Este servicio cobra un monto fijo</label>
            </div>
            <div id="grupo-monto">
                <input type="text"
                       inputmode="numeric"
                       id="servicioMontoInput"
                       class="form-control mb-2"
                       style="display:none;"
                       disabled
                       oninput="window.handleCLPInput(this)"
                       onfocus="if(this.value) this.value = window.stripCLP(this.value)">
            </div>
            <button type="button" id="btnConfirmarServicio" class="btn btn-success mb-3">Confirmar servicio</button>
        </div>

        {{-- 3. Botón para abrir el formulario --}}
        <div>
            <button type="button" id="btnToggleServicio" class="btn btn-secondary mb-2">Agregar servicio</button>
        </div>

        {{-- Hidden inputs for services array --}}
        <template x-for="(serv, index) in servicios" :key="index">
            <div>
                <input type="hidden" :name="'servicios[' + index + '][tipo]'" :value="serv.tipo">
                <input type="hidden" :name="'servicios[' + index + '][dia]'" :value="serv.dia">
                <input type="hidden" :name="'servicios[' + index + '][monto]'" :value="serv.monto || ''">
            </div>
        </template>

        {{-- 4. Submit button — text changes based on whether services exist --}}
        <div>
            <button type="submit"
                   id="btnAddServicio"
                   class="btn btn-success"
                   x-text="servicios.length && servicios[0].tipo !== 'Sin servicios' ? 'Finalizar y agregar administración' : 'Finalizar sin administrar servicios'">
                Finalizar sin administrar servicios
            </button>
        </div>

    </div>
</div>
