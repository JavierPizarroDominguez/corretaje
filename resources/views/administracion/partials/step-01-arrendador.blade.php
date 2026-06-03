{{-- Step 1: Arrendador (idéntico al legacy) --}}
<div class="form-check mb-2" id="wrapper-propiedadCorredor">
    <input type="checkbox" id="propiedadCorredor" name="propiedad_corredor" class="form-check-input" value="1" {{ old('propiedad_corredor') ? 'checked' : '' }}>
    <label class="form-check-label">La propiedad pertenece al corredor</label>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <input type="text"
               id="input-arrendador"
               name="arrendador_nombre"
               class="form-control"
               value="{{ old('arrendador_nombre') }}"
               placeholder="Buscar cliente..."
               autocomplete="off"
               {{ old('propiedad_corredor') ? 'disabled' : '' }}>
        <div style="position:relative;">
            <div id="lista-arrendador" class="list-group position-absolute w-100" style="z-index:1000;"></div>
        </div>
        <input type="hidden" name="arrendador_cliente_id" id="hidden-arrendador-id" value="{{ old('arrendador_cliente_id', '') }}">
        @error('arrendador_nombre')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddArrendador" class="btn btn-primary form-control" {{ old('propiedad_corredor') ? 'disabled' : '' }}>Añadir</button>
    </div>
</div>
