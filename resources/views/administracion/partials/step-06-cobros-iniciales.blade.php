{{-- Step 6: Cobros Iniciales (solo si administracion = true) --}}
<h4 class="mb-4">💰 Cobros Iniciales</h4>
<p class="text-muted">Este paso solo aplica cuando se ha seleccionado administración.</p>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Comisión Inicial (CLP)</label>
        <input type="number"
               name="comision_inicial"
               class="form-control"
               value="{{ old('comision_inicial') }}"
               placeholder="Ej: 150000"
               min="0">
        @error('comision_inicial')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        <small class="form-text text-muted">Monto único cobrado al inicio del contrato.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Garantía (CLP)</label>
        <input type="number"
               name="garantia"
               class="form-control"
               value="{{ old('garantia') }}"
               placeholder="Ej: 450000"
               min="0">
        @error('garantia')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        <small class="form-text text-muted">Monto de garantía solicitado al arrendatario.</small>
    </div>
</div>
