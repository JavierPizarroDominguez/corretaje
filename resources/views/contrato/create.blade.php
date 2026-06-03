@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nuevo contrato</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/contrato">
                @csrf
        <div class="mb-3">
            <label class="form-label">Unidad</label>
            @php
                $currentUnidad = old('Unidad_id') ?? ($contrato->Unidad_id ?? null);
            @endphp
            @if($unidadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-unidad"
                           name="nombre-unidad"
                           class="form-control"
                           placeholder="Buscar Unidad..."
                           value="{{ old('nombre-unidad') }}"
                           autocomplete="off">
                <div id="listaCreateUnidad"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Unidad_id" id="input-create-unidad-id">
                @error('nombre-unidad') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Unidad_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($unidadOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentUnidad == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('Unidad_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Administracion</label>
            <select name="administracion" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="1" {{ old('administracion') === '1' ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ old('administracion') === '0' ? 'selected' : '' }}>No</option>
            </select>
            @error('administracion') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Comision Inicial</label>
            <input type="number" name="comision_inicial" class="form-control" value="{{ old('comision_inicial') }}">
            @error('comision_inicial') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Garantia</label>
            <input type="number" name="garantia" class="form-control" value="{{ old('garantia') }}">
            @error('garantia') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Renta</label>
            <input type="number" name="renta" class="form-control" value="{{ old('renta') }}">
            @error('renta') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Dia Pago</label>
            <input type="number" name="dia_pago" class="form-control" value="{{ old('dia_pago') }}">
            @error('dia_pago') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Comision Mensual</label>
            <input type="number" name="comision_mensual" class="form-control" value="{{ old('comision_mensual') }}">
            @error('comision_mensual') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha Firma</label>
            <input type="datetime-local" name="fecha_firma" class="form-control" value="{{ old('fecha_firma') }}">
            @error('fecha_firma') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha Inicio</label>
            <input type="datetime-local" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}">
            @error('fecha_inicio') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha Termino</label>
            <input type="datetime-local" name="fecha_termino" class="form-control" value="{{ old('fecha_termino') }}">
            @error('fecha_termino') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Url Pdf</label>
            <input type="text" name="url_pdf" class="form-control" value="{{ old('url_pdf') }}">
            @error('url_pdf') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Ciudad</label>
            @php
                $currentCiudad = old('Ciudad_id') ?? ($contrato->Ciudad_id ?? null);
            @endphp
            @if($ciudadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-ciudad"
                           name="nombre-ciudad"
                           class="form-control"
                           placeholder="Buscar Ciudad..."
                           value="{{ old('nombre-ciudad') }}"
                           autocomplete="off">
                <div id="listaCreateCiudad"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Ciudad_id" id="input-create-ciudad-id">
                @error('nombre-ciudad') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Ciudad_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($ciudadOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentCiudad == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('Ciudad_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/contrato" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    buscador({
        input: '#input-create-unidad',
        list:  '#listaCreateUnidad',
        tipo:  'unidad',
        onSelect: function(item) {
            document.getElementById('input-create-unidad').value = item.texto;
            document.getElementById('input-create-unidad-id').value = item.id;
        }
    });

    buscador({
        input: '#input-create-ciudad',
        list:  '#listaCreateCiudad',
        tipo:  'ciudad',
        onSelect: function(item) {
            document.getElementById('input-create-ciudad').value = item.texto;
            document.getElementById('input-create-ciudad-id').value = item.id;
        }
    });
</script>
@endpush
