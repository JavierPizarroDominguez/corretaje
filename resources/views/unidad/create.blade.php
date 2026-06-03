@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nuevo unidad</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/unidad">
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
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/unidad" class="btn btn-outline-secondary btn-sm">Cancelar</a>
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
</script>
@endpush
