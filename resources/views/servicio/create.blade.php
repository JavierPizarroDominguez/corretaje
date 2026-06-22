@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nuevo servicio</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/servicio">
                @csrf
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Luz" {{ old('tipo') === 'Luz' ? 'selected' : '' }}>Luz</option>
                <option value="Agua" {{ old('tipo') === 'Agua' ? 'selected' : '' }}>Agua</option>
                <option value="Gas" {{ old('tipo') === 'Gas' ? 'selected' : '' }}>Gas</option>
                <option value="Gastos Comunes" {{ old('tipo') === 'Gastos Comunes' ? 'selected' : '' }}>Gastos Comunes</option>
            </select>
            @error('tipo') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Dia Pago</label>
            <input type="number" name="dia_pago" class="form-control" value="{{ old('dia_pago') }}">
            @error('dia_pago') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Propiedad</label>
            @php
                $currentPropiedad = old('Propiedad_id') ?? ($servicio->Propiedad_id ?? null);
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
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Activo" {{ old('estado') === 'Activo' ? 'selected' : '' }}>Activo</option>
                <option value="Inactivo" {{ old('estado') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Numero Cliente</label>
            <input type="text" name="numero_cliente" class="form-control" value="{{ old('numero_cliente') }}">
            @error('numero_cliente') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Empresa</label>
            @php
                $currentEmpresa = old('Empresa_id') ?? ($servicio->Empresa_id ?? null);
            @endphp
            @if($empresaCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-empresa"
                           name="nombre-empresa"
                           class="form-control"
                           placeholder="Buscar Empresa..."
                           value="{{ old('nombre-empresa') }}"
                           autocomplete="off">
                <div id="listaCreateEmpresa"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Empresa_id" id="input-create-empresa-id">
                @error('nombre-empresa') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Empresa_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($empresaOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentEmpresa == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('Empresa_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Monto Fijo</label>
            <input type="number" name="monto_fijo" class="form-control" value="{{ old('monto_fijo') }}">
            @error('monto_fijo') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/servicio" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

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

    buscador({
        input: '#input-create-empresa',
        list:  '#listaCreateEmpresa',
        tipo:  'empresa',
        onSelect: function(item) {
            document.getElementById('input-create-empresa').value = item.texto;
            document.getElementById('input-create-empresa-id').value = item.id;
        }
    });
</script>
@endpush
