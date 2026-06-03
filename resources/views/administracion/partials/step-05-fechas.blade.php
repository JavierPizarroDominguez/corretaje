{{-- Step 5: Fechas del Contrato --}}
<h4 class="mb-4">📅 Fechas del Contrato</h4>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Fecha de Firma</label>
        <input type="date"
               name="fecha_firma"
               class="form-control"
               value="{{ old('fecha_firma') }}">
        @error('fecha_firma')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
        <input type="date"
               name="fecha_inicio"
               class="form-control"
               value="{{ old('fecha_inicio') }}">
        @error('fecha_inicio')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Fecha de Término</label>
        <input type="date"
               name="fecha_termino"
               class="form-control"
               value="{{ old('fecha_termino') }}">
        @error('fecha_termino')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        <small class="form-text text-muted">Dejar en blanco para contratos sin fecha de término fija.</small>
    </div>
</div>
