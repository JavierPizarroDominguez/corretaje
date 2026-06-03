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