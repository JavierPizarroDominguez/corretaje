@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>ParticipanteContrato</h2>
        <a href="/participante_contrato/create" class="btn btn-primary">Agregar</a>
    </div>

    
    

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Cliente</th>
                <th>Contrato</th>
                <th>Rol</th>
                <th>Monto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($participanteContratos as $participanteContrato)
            <tr>
                <td>
                    @if($participanteContrato->Cliente_id)
                        <a href="/cliente/{{ $participanteContrato->Cliente_id }}">
                            {{ $participanteContrato->cliente->id ?? $participanteContrato->Cliente_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($participanteContrato->Contrato_id)
                        <a href="/contrato/{{ $participanteContrato->Contrato_id }}">
                            {{ $participanteContrato->contrato->id ?? $participanteContrato->Contrato_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $participanteContrato->rol }}</td>
                <td>{{ $participanteContrato->monto }}</td>
                <td>
                    <a href="/participante_contrato/{{pk_blade_segments}}" class="btn btn-sm btn-outline-primary">Revisar</a>
                    <form method="POST" action="/participante_contrato/{{pk_blade_segments}}" style="display:inline;">
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

    {{ $participanteContratos->links() }}
</div>
@endsection

@push('scripts')
<script>
    // Fix 5: buscador del index navega al show directamente
    buscador({
        input: '#input-buscador-index',
        list:  '#lista-buscador-index',
        tipo:  'participante_contrato'
    });
</script>
@endpush
