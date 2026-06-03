{{--
    modal/create.blade.php — Formulario para crear ParticipanteCobro en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo participantecobro', vista: 'vista-crear-participanteCobro'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-participanteCobro">
                @include('participante_cobro.modal.create')
            </div>
        </div>
--}}

<form method="POST" action="/participante_cobro" id="form-modal-create-participanteCobro">
    @csrf
        <div class="mb-3">
            <label class="form-label">Cliente</label>
            @php
                $currentCliente = old('Cliente_id') ?? ($participanteCobro->Cliente_id ?? null);
            @endphp
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-cliente"
                           name="nombre-cliente"
                           class="form-control"
                           placeholder="Buscar Cliente..."
                           value="{{ old('nombre-cliente') }}"
                           autocomplete="off">
                <div id="listaCreateCliente"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Cliente_id" id="input-create-cliente-id" value="{{ old('Cliente_id') }}">
                @error('nombre-cliente') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Cliente_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentCliente == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Cobro</label>
            @php
                $currentCobro = old('Cobro_id') ?? ($participanteCobro->Cobro_id ?? null);
            @endphp
            @if($cobroCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-cobro"
                           name="id-cobro"
                           class="form-control"
                           placeholder="Buscar Cobro..."
                           value="{{ old('id-cobro') }}"
                           autocomplete="off">
                <div id="listaCreateCobro"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Cobro_id" id="input-create-cobro-id" value="{{ old('Cobro_id') }}">
                @error('id-cobro') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Cobro_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($cobroOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentCobro == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Cobro_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Monto</label>
            <input type="number" name="monto" class="form-control" value="{{ old('monto') }}">
            @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="rol" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Deudor" {{ old('rol') === 'Deudor' ? 'selected' : '' }}>Deudor</option>
                <option value="Acreedor" {{ old('rol') === 'Acreedor' ? 'selected' : '' }}>Acreedor</option>
            </select>
            @error('rol') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>

@push('scripts')
<script>
    buscador({
        input: '#input-create-cliente',
        list:  '#listaCreateCliente',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-create-cliente').value = item.texto;
            document.getElementById('input-create-cliente-id').value = item.id;
        }
    });

    buscador({
        input: '#input-create-cobro',
        list:  '#listaCreateCobro',
        tipo:  'cobro',
        onSelect: function(item) {
            document.getElementById('input-create-cobro').value = item.texto;
            document.getElementById('input-create-cobro-id').value = item.id;
        }
    });
</script>
@endpush
