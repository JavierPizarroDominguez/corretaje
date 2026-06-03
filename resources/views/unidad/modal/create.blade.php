{{--
    modal/create.blade.php — Formulario para crear Unidad en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo unidad', vista: 'vista-crear-unidad'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-unidad">
                @include('unidad.modal.create')
            </div>
        </div>
--}}

<form method="POST" action="/unidad" id="form-modal-create-unidad">
    @csrf
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}">
            @error('nombre') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Propiedad</label>
            @php
                $currentPropiedad = old('Propiedad_id') ?? ($unidad->Propiedad_id ?? null);
            @endphp
            @if($propiedadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="direccion-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-propiedad"
                           name="direccion-propiedad"
                           class="form-control"
                           placeholder="Buscar Propiedad..."
                           value="{{ old('direccion-propiedad') }}"
                           autocomplete="off">
                <div id="listaCreatePropiedad"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Propiedad_id" id="input-create-propiedad-id">
                @error('direccion-propiedad') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Propiedad_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($propiedadOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentPropiedad == $option->id ? 'selected' : '' }}>
                            {{ $option->direccion }}
                        </option>
                    @endforeach
                </select>
                @error('Propiedad_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>

@push('scripts')
<script>
    buscador({
        input: '#input-create-propiedad',
        list:  '#listaCreatePropiedad',
        tipo:  'propiedad',
        onSelect: function(item) {
            document.getElementById('input-create-propiedad').value = item.texto;
            document.getElementById('input-create-propiedad-id').value = item.id;
        }
    });
</script>
@endpush
