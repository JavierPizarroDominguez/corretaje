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