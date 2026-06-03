@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Cobro</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-cobro"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-cobro"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/cobro/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('cobro.filter')

    
    

    <table class="table table-bordered table-hover" id="table-cobro">
        <thead class="table-light">
            <tr>
                <th>Fecha Cobro</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Detalle</th>
                <th>Contrato</th>
                <th>Servicio</th>
                <th>Propiedad</th>
                <th>Unidad</th>
                <th>Deudor</th>
                <th>Acreedor</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cobros as $cobro)
            <tr>
                <td>{{ $cobro->fecha_cobro }}</td>
                <td>{{ $cobro->estado }}</td>
                <td>{{ $cobro->tipo }}</td>
                <td>{{ $cobro->monto }}</td>
                <td>{{ $cobro->detalle }}</td>
                <td>
                    @if($cobro->Contrato_id)
                        <a href="/contrato/{{ $cobro->Contrato_id }}">
                            {{ $cobro->contrato->id ?? $cobro->Contrato_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Servicio_id)
                        <a href="/servicio/{{ $cobro->Servicio_id }}">
                            {{ $cobro->servicio->id ?? $cobro->Servicio_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Propiedad_id)
                        <a href="/propiedad/{{ $cobro->Propiedad_id }}">
                            {{ $cobro->propiedad->direccion ?? $cobro->Propiedad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Unidad_id)
                        <a href="/unidad/{{ $cobro->Unidad_id }}">
                            {{ $cobro->unidad->nombre ?? $cobro->Unidad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->deudor)
                        <a href="/cliente/ficha/{{ $cobro->deudor?->cliente?->id }}">
                            {{ $cobro->deudor?->nombre ?? 'Sin nombre' }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->acreedor)
                        <a href="/cliente/ficha/{{ $cobro->acreedor?->cliente?->id }}">
                            {{ $cobro->acreedor?->nombre ?? 'Sin nombre' }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <a href="/cobro/{{ $cobro->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/cobro/{{ $cobro->id }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('¿Eliminar?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination-area">
        {{ $cobros->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'cobro'
    });

    initFilters({
        baseUrl: '/cobro',
        tableSelector: '#table-cobro',
        filterPanel: '#filter-panel-cobro'
    });
</script>
@endpush
