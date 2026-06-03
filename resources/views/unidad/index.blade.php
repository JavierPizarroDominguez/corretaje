@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Unidad</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-unidad"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-unidad"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/unidad/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('unidad.filter')

    {{-- Buscador del index: navega al show al seleccionar --}}
    <div class="mb-3 position-relative">
        <input id="input-buscador-index"
               type="text"
               class="form-control"
               placeholder="Buscar unidad..."
               autocomplete="off">
        <div id="lista-buscador-index"
             class="list-group position-absolute w-100"
             style="z-index:1000;"></div>
    </div>

    <table class="table table-bordered table-hover" id="table-unidad">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Propiedad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($unidads as $unidad)
            <tr>
                <td>{{ $unidad->nombre }}</td>
                <td>
                    @if($unidad->Propiedad_id)
                        <a href="/propiedad/{{ $unidad->Propiedad_id }}">
                            {{ $unidad->propiedad->direccion ?? $unidad->Propiedad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <a href="/unidad/{{ $unidad->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/unidad/{{ $unidad->id }}" style="display:inline;">
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
        {{ $unidads->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'unidad'
    });

    initFilters({
        baseUrl: '/unidad',
        tableSelector: '#table-unidad',
        filterPanel: '#filter-panel-unidad'
    });
</script>
@endpush
