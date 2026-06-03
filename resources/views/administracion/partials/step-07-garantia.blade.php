{{-- Step 7: Garantía (idéntico al legacy) --}}
<div class="row mb-3" id="step-garantia">
    <div class="col-md-6">
        <label for="garantiaInput">Monto garantía</label>
        <input type="number"
               id="garantiaInput"
               name="garantia"
               class="form-control"
               min="0"
               value="{{ old('garantia') }}">
        @error('garantia')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <div class="form-check mt-2">
            <input type="checkbox"
                   id="noGarantia"
                   name="no_garantia"
                   class="form-check-input"
                   value="1"
                   {{ old('no_garantia') ? 'checked' : '' }}>
            <label class="form-check-label">No cobrar garantía</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddGarantia" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>