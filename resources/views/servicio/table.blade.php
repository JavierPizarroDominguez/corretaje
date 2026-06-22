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