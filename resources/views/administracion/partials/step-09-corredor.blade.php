{{-- Step 9: Datos del Corredor --}}
<h4 class="mb-4">🏢 Corredor de la Operación</h4>

<input type="hidden" name="corredor_cliente_id" value="1">

<div class="alert alert-info">
    <i class="ti ti-info-circle"></i>
    El corredor asociado a esta operación es <strong>Cliente ID #1</strong> (corredor predeterminado del sistema).
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">URL del Contrato (PDF)</label>
        <input type="url"
               name="url_pdf"
               class="form-control"
               value="{{ old('url_pdf') }}"
               placeholder="https://...">
        @error('url_pdf')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="alert alert-secondary mt-3">
    <i class="ti ti-check"></i>
    Al hacer clic en "Crear Administración" (botón verde abajo), se crearán todas las entidades asociadas:
    arrendador, arrendatario, propiedad, contrato y participantes.
</div>
