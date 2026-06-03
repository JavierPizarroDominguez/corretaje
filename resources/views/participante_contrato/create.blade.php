@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nuevo participantecontrato</h2>

    <form method="POST" action="/participante_contrato">
        @csrf
        <div class="mb-3">
            <label class="form-label">Cliente</label>
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-cliente"
                           name="id-cliente"
                           class="form-control"
                           placeholder="Buscar Cliente..."
                           value="{{ old('id-cliente') }}"
                           autocomplete="off">
                    <div id="listaCreateCliente"
                         class="list-group position-absolute w-100"
                         style="z-index:1000;"></div>
                </div>
                @error('id-cliente') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Cliente_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('Cliente_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Contrato</label>
            @if($contratoCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-contrato"
                           name="id-contrato"
                           class="form-control"
                           placeholder="Buscar Contrato..."
                           value="{{ old('id-contrato') }}"
                           autocomplete="off">
                    <div id="listaCreateContrato"
                         class="list-group position-absolute w-100"
                         style="z-index:1000;"></div>
                </div>
                @error('id-contrato') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Contrato_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($contratoOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ old('Contrato_id') == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Contrato_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="rol" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="arrendatario" {{ old('rol') === 'arrendatario' ? 'selected' : '' }}>arrendatario</option>
                <option value="arrendador" {{ old('rol') === 'arrendador' ? 'selected' : '' }}>arrendador</option>
                <option value="corredor" {{ old('rol') === 'corredor' ? 'selected' : '' }}>corredor</option>
                <option value="co-arrendatario" {{ old('rol') === 'co-arrendatario' ? 'selected' : '' }}>co-arrendatario</option>
                <option value="co-arrendador" {{ old('rol') === 'co-arrendador' ? 'selected' : '' }}>co-arrendador</option>
            </select>
            @error('rol') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div>
            <label>Monto</label>
            <input type="number" name="monto" value="{{ old('monto') }}">
            @error('monto') <span>{{ $message }}</span> @enderror
        </div>
        <button type="submit">Guardar</button>
        <a href="/participante_contrato">Cancelar</a>
    </form>
</div>
@endsection

@push('scripts')
<script>

</script>
@endpush
