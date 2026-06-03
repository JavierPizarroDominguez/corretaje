{{-- Step 4: Administración (idéntico al legacy) --}}
<div class="row mb-3" id="step-administracion">
    <div class="col-md-6">
        <label for="rentaInput">Monto de renta</label>
        <input type="number"
               id="rentaInput"
               name="renta"
               class="form-control"
               placeholder="Ej: 500000"
               min="0"
               value="{{ old('renta') }}">
        @error('renta')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <label for="diaPagoInput" style="margin-top:.5rem;">Día de pago</label>
        <input type="number"
               id="diaPagoInput"
               name="dia_pago"
               class="form-control"
               placeholder="Ej: 5"
               min="1"
               max="31"
               value="{{ old('dia_pago') }}">
        @error('dia_pago')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <div class="form-check mt-2" id="wrapper-sinAdministracion">
            <input type="checkbox"
                   id="sinAdministracion"
                   name="sin_administracion"
                   class="form-check-input"
                   value="1"
                   {{ old('sin_administracion') ? 'checked' : '' }}>
            <label for="sinAdministracion" class="form-check-label">Contrato sin administración</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddAdmin" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>
