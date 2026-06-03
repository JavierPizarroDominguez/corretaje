@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Propiedad</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-propiedad"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-propiedad"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/propiedad/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('propiedad.filter')

    
    

    <table class="table table-bordered table-hover" id="table-propiedad">
        <thead class="table-light">
            <tr>
                <th>Direccion</th>
                <th>Propietario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($propiedads as $propiedad)
            <tr>
                <td>{{ $propiedad->direccion }}</td>
                <td>
                    @if($propiedad->propietario)
                        <a href="/cliente/{{ $propiedad->propietario }}">
                            {{ $propiedad->propietario->nombre ?? $propiedad->propietario }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <a href="/propiedad/{{ $propiedad->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/propiedad/{{ $propiedad->id }}" style="display:inline;">
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
        {{ $propiedads->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'propiedad'
    });

    initFilters({
        baseUrl: '/propiedad',
        tableSelector: '#table-propiedad',
        filterPanel: '#filter-panel-propiedad'
    });
</script>
@endpush
