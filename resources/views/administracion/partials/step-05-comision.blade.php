{{-- Step 5: Comisión Inicial (idéntico al legacy) --}}
<div class="row mb-3" id="step-comision">
    <div class="col-md-6">
        <label for="comisionMontoInput">Monto comisión inicial</label>
        <input type="number"
               id="comisionMontoInput"
               name="comision_inicial"
               class="form-control"
               placeholder="Ej: 200000"
               min="0"
               value="{{ old('comision_inicial') }}">
        @error('comision_inicial')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <div class="form-check mt-2">
            <input type="checkbox"
                   id="cobrarArrendador"
                   name="cobrar_arrendador"
                   class="form-check-input"
                   value="1"
                   {{ old('cobrar_arrendador', '1') ? 'checked' : '' }}>
            <label for="cobrarArrendador" class="form-check-label">Cobrar al Arrendador</label>
        </div>

        <div class="form-check">
            <input type="checkbox"
                   id="cobrarArrendatario"
                   name="cobrar_arrendatario"
                   class="form-check-input"
                   value="1"
                   {{ old('cobrar_arrendatario', '1') ? 'checked' : '' }}>
            <label for="cobrarArrendatario" class="form-check-label">Cobrar al Arrendatario</label>
        </div>

        <div class="form-check mt-1">
            <input type="checkbox"
                   id="noComisionInicial"
                   name="no_comision_inicial"
                   class="form-check-input"
                   value="1"
                   {{ old('no_comision_inicial') ? 'checked' : '' }}>
            <label for="noComisionInicial" class="form-check-label">No generar comisión inicial</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddComision" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>