{{--
    modal/create.blade.php — Formulario para crear Transaccion en modal

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Nuevo transaccion', vista: 'vista-crear-transaccion'})">
            Agregar
        </button>

        <div class="d-none">
            <div id="vista-crear-transaccion">
                @include('transaccion.modal.create')
            </div>
        </div>
--}}

<form method="POST" action="/transaccion" id="form-modal-create-transaccion">
    @csrf
        <div>
            <label>Monto</label>
            <input type="number" name="monto" value="{{ old('monto') }}">
            @error('monto') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label>Fecha</label>
            <input type="datetime-local" name="fecha" value="{{ old('fecha') }}">
            @error('fecha') <span>{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Destino Transaccion</label>
            @if($destinotransaccionCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-destino_transaccion"
                           name="id-destino_transaccion"
                           class="form-control"
                           placeholder="Buscar Destino Transaccion..."
                           value="{{ old('id-destino_transaccion') }}"
                           autocomplete="off">
                    <div id="listaCreateDestinoTransaccion"
                         class="list-group position-absolute w-100"
                         style="z-index:1000;"></div>
                </div>
                @error('id-destino_transaccion') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Destino_Transaccion_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($destinotransaccionOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('Destino_Transaccion_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Destino_Transaccion_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Origen Transaccion</label>
            @if($origentransaccionCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-origen_transaccion"
                           name="id-origen_transaccion"
                           class="form-control"
                           placeholder="Buscar Origen Transaccion..."
                           value="{{ old('id-origen_transaccion') }}"
                           autocomplete="off">
                    <div id="listaCreateOrigenTransaccion"
                         class="list-group position-absolute w-100"
                         style="z-index:1000;"></div>
                </div>
                @error('id-origen_transaccion') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Origen_Transaccion_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($origentransaccionOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('Origen_Transaccion_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Origen_Transaccion_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div>
            <label>Url Comprobante</label>
            <input type="text" name="url_comprobante" value="{{ old('url_comprobante') }}">
            @error('url_comprobante') <span>{{ $message }}</span> @enderror
        </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>
