{{-- Step 2: Arrendatario (idéntico al legacy) --}}
<div class="row mb-3">
    <div class="col-md-6">
        <input type="text"
               id="input-arrendatario"
               name="arrendatario_nombre"
               class="form-control"
               value="{{ old('arrendatario_nombre') }}"
               placeholder="Nombre del cliente"
               autocomplete="off">
        <div style="position:relative;">
            <div id="lista-arrendatario" class="list-group position-absolute w-100" style="z-index:1000;"></div>
        </div>
        <input type="hidden" name="arrendatario_cliente_id" id="hidden-arrendatario-id" value="{{ old('arrendatario_cliente_id', '') }}">
        @error('arrendatario_nombre')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-md-2">
        <button type="button" id="btnAddArrendatario" class="btn btn-primary form-control">Añadir</button>
    </div>
</div>
