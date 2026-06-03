@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>ParticipanteCobro</h2>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    id="btn-toggle-filter-participante_cobro"
                    data-bs-toggle="collapse"
                    data-bs-target="#filter-panel-participante_cobro"
                    aria-expanded="false">
                <i class="ti ti-filter"></i> Filtrar
            </button>
            <a href="/participante_cobro/create" class="btn btn-primary">Agregar</a>
        </div>
    </div>

    {{-- Panel de filtros colapsable --}}
    @include('participante_cobro.filter')

    
    

    <table class="table table-bordered table-hover" id="table-participante_cobro">
        <thead class="table-light">
            <tr>
                <th>Cliente</th>
                <th>Cobro</th>
                <th>Monto</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($participanteCobros as $participanteCobro)
            <tr>
                <td>
                    @if($participanteCobro->Cliente_id)
                        <a href="/cliente/{{ $participanteCobro->Cliente_id }}">
                            {{ $participanteCobro->cliente->nombre ?? $participanteCobro->Cliente_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($participanteCobro->Cobro_id)
                        <a href="/cobro/{{ $participanteCobro->Cobro_id }}">
                            {{ $participanteCobro->cobro->id ?? $participanteCobro->Cobro_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $participanteCobro->monto }}</td>
                <td>{{ $participanteCobro->rol }}</td>
                <td>
                    <a href="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}" style="display:inline;">
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
        {{ $participanteCobros->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/filtros.js') }}"></script>
<script>
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'participante_cobro'
    });

    initFilters({
        baseUrl: '/participante_cobro',
        tableSelector: '#table-participante_cobro',
        filterPanel: '#filter-panel-participante_cobro'
    });
</script>
@endpush
