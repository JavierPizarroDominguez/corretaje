@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Contrato</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-contrato"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-contrato"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/contrato/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('contrato.filter')

    
    

    <table class="table table-bordered table-hover" id="table-contrato">
        <thead class="table-light">
            <tr>
                <th>Unidad</th>
                <th>Administracion</th>
                <th>Comision Inicial</th>
                <th>Garantia</th>
                <th>Renta</th>
                <th>Dia Pago</th>
                <th>Comision Mensual</th>
                <th>Fecha Firma</th>
                <th>Fecha Inicio</th>
                <th>Fecha Termino</th>
                <th>Url Pdf</th>
                <th>Ciudad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contratos as $contrato)
            <tr>
                <td>
                    @if($contrato->Unidad_id)
                        <a href="/unidad/{{ $contrato->Unidad_id }}">
                            {{ $contrato->unidad->nombre ?? $contrato->Unidad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $contrato->administracion }}</td>
                <td>{{ $contrato->comision_inicial }}</td>
                <td>{{ $contrato->garantia }}</td>
                <td>{{ $contrato->renta }}</td>
                <td>{{ $contrato->dia_pago }}</td>
                <td>{{ $contrato->comision_mensual }}</td>
                <td>{{ $contrato->fecha_firma }}</td>
                <td>{{ $contrato->fecha_inicio }}</td>
                <td>{{ $contrato->fecha_termino }}</td>
                <td>{{ $contrato->url_pdf }}</td>
                <td>
                    @if($contrato->Ciudad_id)
                        <a href="/ciudad/{{ $contrato->Ciudad_id }}">
                            {{ $contrato->ciudad->nombre ?? $contrato->Ciudad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <a href="/contrato/{{ $contrato->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/contrato/{{ $contrato->id }}" style="display:inline;">
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
        {{ $contratos->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'contrato'
    });

    initFilters({
        baseUrl: '/contrato',
        tableSelector: '#table-contrato',
        filterPanel: '#filter-panel-contrato'
    });
</script>
@endpush
