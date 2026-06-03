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