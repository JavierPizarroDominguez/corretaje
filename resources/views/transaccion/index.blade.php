@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Transaccion</h2>
        <a href="/transaccion/create" class="btn btn-primary">Agregar</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-toggle-filter-transaccion" data-bs-toggle="collapse" data-bs-target="#filter-panel-transaccion" aria-expanded="false"><i class="bi bi-funnel"></i> Filtrar</button>

        @include('transaccion.filter')
    </div>

    
    

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Destino Transaccion</th>
                <th>Origen Transaccion</th>
                <th>Url Comprobante</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaccions as $transaccion)
            <tr>
                <td>{{ $transaccion->monto }}</td>
                <td>{{ $transaccion->fecha }}</td>
                <td>
                    @if($transaccion->Destino_Transaccion_id)
                        <a href="/destino_transaccion/{{ $transaccion->Destino_Transaccion_id }}">
                            {{ $transaccion->destino_transaccion->id ?? $transaccion->Destino_Transaccion_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($transaccion->Origen_Transaccion_id)
                        <a href="/origen_transaccion/{{ $transaccion->Origen_Transaccion_id }}">
                            {{ $transaccion->origen_transaccion->id ?? $transaccion->Origen_Transaccion_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $transaccion->url_comprobante }}</td>
                <td>
                    <a href="/transaccion/{{ $transaccion->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/transaccion/{{ $transaccion->id }}" style="display:inline;">
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

    {{ $transaccions->links() }}
</div>
@endsection

@push('scripts')
<script>
    // Fix 5: buscador del index navega al show directamente
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'transaccion'
    });
</script>

    initFilters({
        baseUrl: '/transaccion',
        tableSelector: '#table-transaccion',
        filterPanel: '#filter-panel-transaccion'
    });
@endpush
