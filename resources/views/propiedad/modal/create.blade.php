{{--
    modal/create.blade.php — Formulario para crear Propiedad en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo propiedad', vista: 'vista-crear-propiedad'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-propiedad">
                @include('propiedad.modal.create')
            </div>
        </div>
--}}

<form method="POST" action="/propiedad" id="form-modal-create-propiedad">
    @csrf
        <div class="mb-3">
            <label class="form-label">Direccion</label>
            <input type="text" name="direccion" class="form-control" value="{{ old('direccion') }}">
            @error('direccion') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Propietario</label>
            @php
                $currentPropietario = old('propietario') ?? ($propiedad->propietario ?? null);
            @endphp
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-propietario"
                           name="nombre-propietario"
                           class="form-control"
                           placeholder="Buscar Propietario..."
                           value="{{ old('nombre-propietario') }}"
                           autocomplete="off">
                <div id="listaCreatePropietario"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="propietario" id="input-create-propietario-id">
                @error('nombre-propietario') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="propietario" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentPropietario == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('propietario') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>

@push('scripts')
<script>
    buscador({
        input: '#input-create-propietario',
        list:  '#listaCreatePropietario',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-create-propietario').value = item.texto;
            document.getElementById('input-create-propietario-id').value = item.id;
        }
    });
</script>
@endpush
