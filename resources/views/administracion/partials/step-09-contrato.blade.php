{{-- Step 9: Contrato (idéntico al legacy) --}}
<div class="row mb-3" id="step-contrato">
    <div class="col-md-6">
        <label for="contratoFile">Subir contrato</label>
        <input type="file"
               id="contratoFile"
               name="contrato_file"
               class="form-control">
        @error('contrato_file')
            <span class="text-danger">{{ $message }}</span>
        @enderror

        <button type="button"
                id="btnGenerarContrato"
                class="btn btn-secondary mt-2">Generar contrato</button>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <input type="submit"
               id="btnGuardarAdmin"
               class="btn btn-primary"
               value="Guardar Administración">
    </div>
</div>