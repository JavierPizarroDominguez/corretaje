@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Cliente</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-cliente"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-cliente"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/cliente/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('cliente.filter')

    {{-- Buscador del index: navega al show al seleccionar --}}
    <div class="mb-3 position-relative">
        <input id="input-buscador-index"
               type="text"
               class="form-control"
               placeholder="Buscar cliente..."
               autocomplete="off">
        <div id="lista-buscador-index"
             class="list-group position-absolute w-100"
             style="z-index:1000;"></div>
    </div>

    <table class="table table-bordered table-hover" id="table-cliente">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Fecha Creacion</th>
                <th>Rut</th>
                <th>Email</th>
                <th>Ocupacion</th>
                <th>Nacionalidad</th>
                <th>Estado Civil</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $cliente)
            <tr>
                <td>{{ $cliente->nombre }}</td>
                <td>{{ $cliente->fecha_creacion }}</td>
                <td>{{ $cliente->rut }}</td>
                <td>{{ $cliente->email }}</td>
                <td>{{ $cliente->ocupacion }}</td>
                <td>
                    @if($cliente->Nacionalidad_id)
                        <a href="/nacionalidad/{{ $cliente->Nacionalidad_id }}">
                            {{ $cliente->nacionalidad->nombre ?? $cliente->Nacionalidad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $cliente->estado_civil }}</td>
                <td>
                    <a href="/cliente/{{ $cliente->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/cliente/{{ $cliente->id }}" style="display:inline;">
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
        {{ $clientes->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'cliente'
    });

    initFilters({
        baseUrl: '/cliente',
        tableSelector: '#table-cliente',
        filterPanel: '#filter-panel-cliente'
    });
</script>
@endpush
