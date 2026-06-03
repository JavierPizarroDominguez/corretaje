@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Nuevo cobro</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/cobro">
                @csrf
        <div class="mb-3">
            <label class="form-label">Fecha Cobro</label>
            <input type="datetime-local" name="fecha_cobro" class="form-control" value="{{ old('fecha_cobro') }}">
            @error('fecha_cobro') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Pagado" {{ old('estado') === 'Pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="Incompleto" {{ old('estado') === 'Incompleto' ? 'selected' : '' }}>Incompleto</option>
                <option value="Pendiente" {{ old('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Vencido" {{ old('estado') === 'Vencido' ? 'selected' : '' }}>Vencido</option>
                <option value="Anulado" {{ old('estado') === 'Anulado' ? 'selected' : '' }}>Anulado</option>
            </select>
            @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">— Seleccionar —</option>
                <option value="Ingreso Renta Arrendatario" {{ old('tipo') === 'Ingreso Renta Arrendatario' ? 'selected' : '' }}>Ingreso Renta Arrendatario</option>
                <option value="Egreso Renta Arrendador" {{ old('tipo') === 'Egreso Renta Arrendador' ? 'selected' : '' }}>Egreso Renta Arrendador</option>
                <option value="Comision inicial arrendador" {{ old('tipo') === 'Comision inicial arrendador' ? 'selected' : '' }}>Comision inicial arrendador</option>
                <option value="Comision inicial arrendatario" {{ old('tipo') === 'Comision inicial arrendatario' ? 'selected' : '' }}>Comision inicial arrendatario</option>
                <option value="Comision Mensual" {{ old('tipo') === 'Comision Mensual' ? 'selected' : '' }}>Comision Mensual</option>
                <option value="Ingreso Garantía Arrendatario" {{ old('tipo') === 'Ingreso Garantía Arrendatario' ? 'selected' : '' }}>Ingreso Garantía Arrendatario</option>
                <option value="Egreso Garantía Arrendador" {{ old('tipo') === 'Egreso Garantía Arrendador' ? 'selected' : '' }}>Egreso Garantía Arrendador</option>
                <option value="Devolución Garantía Arrendatario" {{ old('tipo') === 'Devolución Garantía Arrendatario' ? 'selected' : '' }}>Devolución Garantía Arrendatario</option>
                <option value="Aseo Final" {{ old('tipo') === 'Aseo Final' ? 'selected' : '' }}>Aseo Final</option>
                <option value="Luz" {{ old('tipo') === 'Luz' ? 'selected' : '' }}>Luz</option>
                <option value="Agua" {{ old('tipo') === 'Agua' ? 'selected' : '' }}>Agua</option>
                <option value="Gas" {{ old('tipo') === 'Gas' ? 'selected' : '' }}>Gas</option>
                <option value="Gastos comunes" {{ old('tipo') === 'Gastos comunes' ? 'selected' : '' }}>Gastos comunes</option>
                <option value="Reparación" {{ old('tipo') === 'Reparación' ? 'selected' : '' }}>Reparación</option>
                <option value="Extra" {{ old('tipo') === 'Extra' ? 'selected' : '' }}>Extra</option>
                <option value="Devolución" {{ old('tipo') === 'Devolución' ? 'selected' : '' }}>Devolución</option>
            </select>
            @error('tipo') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Monto</label>
            <input type="number" name="monto" class="form-control" value="{{ old('monto') }}">
            @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Detalle</label>
            <input type="text" name="detalle" class="form-control" value="{{ old('detalle') }}">
            @error('detalle') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Contrato</label>
            @php
                $currentContrato = old('Contrato_id') ?? ($cobro->Contrato_id ?? null);
            @endphp
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
                <input type="hidden" name="Contrato_id" id="input-create-contrato-id" value="{{ old('Contrato_id') }}">
                @error('id-contrato') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Contrato_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($contratoOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentContrato == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Contrato_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Servicio</label>
            @php
                $currentServicio = old('Servicio_id') ?? ($cobro->Servicio_id ?? null);
            @endphp
            @if($servicioCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="id-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-servicio"
                           name="id-servicio"
                           class="form-control"
                           placeholder="Buscar Servicio..."
                           value="{{ old('id-servicio') }}"
                           autocomplete="off">
                <div id="listaCreateServicio"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="Servicio_id" id="input-create-servicio-id" value="{{ old('Servicio_id') }}">
                @error('id-servicio') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="Servicio_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($servicioOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentServicio == $option->id ? 'selected' : '' }}>
                            {{ $option->id }}
                        </option>
                    @endforeach
                </select>
                @error('Servicio_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Propiedad</label>
            @php
                $currentPropiedad = old('Propiedad_id') ?? ($cobro->Propiedad_id ?? null);
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
                <input type="hidden" name="Propiedad_id" id="input-create-propiedad-id" value="{{ old('Propiedad_id') }}">
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
            <label class="form-label">Unidad</label>
            @php
                $currentUnidad = old('Unidad_id') ?? ($cobro->Unidad_id ?? null);
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
                <input type="hidden" name="Unidad_id" id="input-create-unidad-id" value="{{ old('Unidad_id') }}">
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
            <label class="form-label">Deudor</label>
            @php
                $currentDeudorId = old('deudor_Cliente_id') ?? ($cobro->deudor?->Cliente_id ?? null);
            @endphp
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-deudor"
                           name="nombre-deudor"
                           class="form-control"
                           placeholder="Buscar Deudor..."
                           value="{{ old('nombre-deudor') }}"
                           autocomplete="off">
                <div id="listaCreateDeudor"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="deudor_Cliente_id" id="input-create-deudor-id" value="{{ old('deudor_Cliente_id') }}">
                @error('nombre-deudor') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="deudor_Cliente_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentDeudorId == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('deudor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Acreedor</label>
            @php
                $currentAcreedorId = old('acreedor_Cliente_id') ?? ($cobro->acreedor?->Cliente_id ?? null);
            @endphp
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                {{-- name="nombre-{{referenced_table}}" evita colisión con campos propios --}}
                <div class="position-relative">
                    <input id="input-create-acreedor"
                           name="nombre-acreedor"
                           class="form-control"
                           placeholder="Buscar Acreedor..."
                           value="{{ old('nombre-acreedor') }}"
                           autocomplete="off">
                <div id="listaCreateAcreedor"
                     class="list-group position-absolute w-100"
                     style="z-index:1000;"></div>
                </div>
                <input type="hidden" name="acreedor_Cliente_id" id="input-create-acreedor-id" value="{{ old('acreedor_Cliente_id') }}">
                @error('nombre-acreedor') <span class="text-danger">{{ $message }}</span> @enderror
            @else
                {{-- Select: pocos registros, envía el id directamente --}}
                <select name="acreedor_Cliente_id" class="form-select">
                    <option value="">— Seleccionar —</option>
                    @foreach($clienteOptions as $option)
                        <option value="{{ $option->id }}"
                                {{ $currentAcreedorId == $option->id ? 'selected' : '' }}>
                            {{ $option->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('acreedor_Cliente_id') <span class="text-danger">{{ $message }}</span> @enderror
            @endif
        </div>
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/cobro" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    buscador({
        input: '#input-create-contrato',
        list:  '#listaCreateContrato',
        tipo:  'contrato',
        onSelect: function(item) {
            document.getElementById('input-create-contrato').value = item.texto;
            document.getElementById('input-create-contrato-id').value = item.id;
        }
    });

    buscador({
        input: '#input-create-servicio',
        list:  '#listaCreateServicio',
        tipo:  'servicio',
        onSelect: function(item) {
            document.getElementById('input-create-servicio').value = item.texto;
            document.getElementById('input-create-servicio-id').value = item.id;
        }
    });

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
        input: '#input-create-unidad',
        list:  '#listaCreateUnidad',
        tipo:  'unidad',
        onSelect: function(item) {
            document.getElementById('input-create-unidad').value = item.texto;
            document.getElementById('input-create-unidad-id').value = item.id;
        }
    });

    buscador({
        input: '#input-create-deudor',
        list:  '#listaCreateDeudor',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-create-deudor').value = item.texto;
            document.getElementById('input-create-deudor-id').value = item.id;
        }
    });

    buscador({
        input: '#input-create-acreedor',
        list:  '#listaCreateAcreedor',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-create-acreedor').value = item.texto;
            document.getElementById('input-create-acreedor-id').value = item.id;
        }
    });
</script>
@endpush
