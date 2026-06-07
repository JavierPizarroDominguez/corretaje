{{-- Step 4: Administración (idéntico al legacy) --}}
<div class="row mb-3" id="step-administracion">
    <div class="col-md-6">
        <label for="rentaInput">Monto de renta</label>
        <input type="text"
               inputmode="numeric"
               id="rentaInput"
               name="renta"
               class="form-control"
               placeholder="Ej: 500000"
               value="{{ old('renta') }}"
               oninput="window.handleCLPInput(this)"
               onfocus="if(this.value) this.value = window.stripCLP(this.value)">
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
               max="28"
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
