@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Servicio</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-servicio"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-servicio"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/servicio/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('servicio.filter')

    
    

    <table class="table table-bordered table-hover" id="table-servicio">
        <thead class="table-light">
            <tr>
                <th>Tipo</th>
                <th>Dia Pago</th>
                <th>Propiedad</th>
                <th>Estado</th>
                <th>Numero Cliente</th>
                <th>Empresa</th>
                <th>Monto Fijo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servicios as $servicio)
            <tr>
                <td>{{ $servicio->tipo }}</td>
                <td>{{ $servicio->dia_pago }}</td>
                <td>
                    @if($servicio->Propiedad_id)
                        <a href="/propiedad/{{ $servicio->Propiedad_id }}">
                            {{ $servicio->propiedad->direccion ?? $servicio->Propiedad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $servicio->estado }}</td>
                <td>{{ $servicio->numero_cliente }}</td>
                <td>
                    @if($servicio->Empresa_id)
                        <a href="/empresa/{{ $servicio->Empresa_id }}">
                            {{ $servicio->empresa->nombre ?? $servicio->Empresa_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $servicio->monto_fijo }}</td>
                <td>
                    <a href="/servicio/{{ $servicio->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/servicio/{{ $servicio->id }}" style="display:inline;">
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
        {{ $servicios->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'servicio'
    });

    initFilters({
        baseUrl: '/servicio',
        tableSelector: '#table-servicio',
        filterPanel: '#filter-panel-servicio'
    });
</script>
@endpush
