{{-- Step 3: Propiedad (idéntico al legacy) --}}
<div class="row mb-3" id="step-propiedad">
    <div class="col-md-6">
        <select id="propiedadSelect" class="form-control" name="propiedad_select">
            <option value="">Seleccionar propiedad...</option>
            <option value="nueva">➕ Agregar nueva propiedad</option>
        </select>
        <input type="text"
               id="nuevaPropiedadInput"
               name="propiedad_direccion"
               class="form-control mt-2"
               placeholder="Ingrese dirección..."
               style="display:none;"
               value="{{ old('propiedad_direccion') }}">
        <input type="hidden" name="propiedad_id" id="hidden-propiedad-id" value="{{ old('propiedad_id', '') }}">
        <input type="hidden" name="unidad_id" id="hidden-unidad-id" value="{{ old('unidad_id', '') }}">
        @error('propiedad_direccion')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddPropiedad" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>