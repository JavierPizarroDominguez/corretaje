{{-- Step 6: Egreso / Comisión Mensual (idéntico al legacy) --}}
<div class="row mb-3" id="step-egreso">
    <div class="col-md-6">
        <label for="egresoRentaInput">Egreso Renta Arrendador</label>
        <input type="number"
               id="egresoRentaInput"
               name="egreso_renta"
               class="form-control"
               min="0"
               value="{{ old('egreso_renta') }}">
        @error('egreso_renta')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <label for="comisionMensualInput" class="mt-2">Comisión Mensual</label>
        <input type="number"
               id="comisionMensualInput"
               name="comision_mensual"
               class="form-control"
               min="0"
               value="{{ old('comision_mensual') }}">
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