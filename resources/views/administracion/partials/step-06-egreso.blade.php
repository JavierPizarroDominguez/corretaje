{{-- Step 6: Egreso / Comisión Mensual (idéntico al legacy) --}}
<div class="row mb-3" id="step-egreso">
    <div class="col-md-6">
        <label for="egresoRentaInput">Transferencia al Arrendador</label>
        <input type="text"
               inputmode="numeric"
               id="egresoRentaInput"
               name="egreso_renta"
               class="form-control"
               value="{{ old('egreso_renta') }}"
               oninput="window.handleCLPInput(this)"
               onfocus="if(this.value) this.value = window.stripCLP(this.value)">
        @error('egreso_renta')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <label for="comisionMensualInput" class="mt-2">Comisión Mensual</label>
        <input type="text"
               inputmode="numeric"
               id="comisionMensualInput"
               name="comision_mensual"
               class="form-control"
               value="{{ old('comision_mensual') }}"
               oninput="window.handleCLPInput(this)"
               onfocus="if(this.value) this.value = window.stripCLP(this.value)">
        @error('comision_mensual')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <div class="form-check">
            <input type="checkbox"
                   id="noComisionMensual"
                   name="no_comision_mensual"
                   class="form-check-input"
                   value="1"
                   {{ old('no_comision_mensual') ? 'checked' : '' }}>
            <label class="form-check-label">No generar comisión</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddEgreso" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>