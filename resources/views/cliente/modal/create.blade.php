{{--
    modal/create.blade.php — Formulario para crear Cliente en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo cliente', vista: 'vista-crear-cliente'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-cliente">
                @include('cliente.modal.create')
            </div>
        </div>
--}}

<form method="POST" action="/cliente" id="form-modal-create-cliente">
    @csrf
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}">
            @error('nombre') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha Creacion</label>
            <input type="datetime-local" name="fecha_creacion" class="form-control" value="{{ old('fecha_creacion') }}">
            @error('fecha_creacion') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Rut</label>
            <input type="text" name="rut" class="form-control" value="{{ old('rut') }}">
            @error('rut') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="text" name="email" class="form-control" value="{{ old('email') }}">
            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Ocupacion</label>
            <input type="text" name="ocupacion" class="form-control" value="{{ old('ocupacion') }}">
            @error('ocupacion') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Nacionalidad</label>
            @php
                $currentNacionalidad = old('Nacionalidad_id') ?? ($cliente->Nacionalidad_id ?? null);
            @endphp
            @if($nacionalidadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-nacionalidad"
                           name="nombre-nacionalidad"
                           class="form-control"
                           placeholder="Buscar Nacionalidad..."
                           value="{{ old('nombre-nacionalidad') }}"
                           autocomplete="off">
                <div id="listaCreateNacionalidad"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Nacionalidad_id" id="input-create-nacionalidad-id">
                @error('nombre-nacionalidad') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Nacionalidad_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($nacionalidadOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentNacionalidad == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('Nacionalidad_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Estado Civil</label>
            <select name="estado_civil" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Soltero" {{ old('estado_civil') === 'Soltero' ? 'selected' : '' }}>Soltero</option>
                <option value="Casado" {{ old('estado_civil') === 'Casado' ? 'selected' : '' }}>Casado</option>
                <option value="Viudo" {{ old('estado_civil') === 'Viudo' ? 'selected' : '' }}>Viudo</option>
                <option value="Divorciado" {{ old('estado_civil') === 'Divorciado' ? 'selected' : '' }}>Divorciado</option>
            </select>
            @error('estado_civil') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>

@push('scripts')
<script>
    buscador({
        input: '#input-create-nacionalidad',
        list:  '#listaCreateNacionalidad',
        tipo:  'nacionalidad',
        onSelect: function(item) {
            document.getElementById('input-create-nacionalidad').value = item.texto;
            document.getElementById('input-create-nacionalidad-id').value = item.id;
        }
    });
</script>
@endpush
