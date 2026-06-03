{{-- Step 7: Servicios (solo si administracion = true y dia_pago esta presente) --}}
<h4 class="mb-4">🔧 Servicios Incluidos</h4>
<p class="text-muted">Seleccione los servicios que estarán incluidos en la administración.</p>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="checkbox"
                   name="servicio_Luz"
                   id="servicio_Luz"
                   class="form-check-input"
                   value="1"
                   {{ old('servicio_Luz') ? 'checked' : '' }}>
            <label class="form-check-label" for="servicio_Luz">
                💡 Luz
            </label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="checkbox"
                   name="servicio_Agua"
                   id="servicio_Agua"
                   class="form-check-input"
                   value="1"
                   {{ old('servicio_Agua') ? 'checked' : '' }}>
            <label class="form-check-label" for="servicio_Agua">
                💧 Agua
            </label>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="checkbox"
                   name="servicio_Gas"
                   id="servicio_Gas"
                   class="form-check-input"
                   value="1"
                   {{ old('servicio_Gas') ? 'checked' : '' }}>
            <label class="form-check-label" for="servicio_Gas">
                🔥 Gas
            </label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="checkbox"
                   name="servicio_Gastos comunes"
                   id="servicio_Gastos_comunes"
                   class="form-check-input"
                   value="1"
                   {{ old('servicio_Gastos comunes') ? 'checked' : '' }}>
            <label class="form-check-label" for="servicio_Gastos_comunes">
                🏢 Gastos Comunes
            </label>
        </div>
    </div>
</div>
