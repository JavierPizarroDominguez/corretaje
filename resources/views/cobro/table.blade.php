@foreach ($cobros as $cobro)
<tr>
                <td>{{ $cobro->fecha_cobro }}</td>
                <td>{{ $cobro->estado }}</td>
                <td>{{ $cobro->tipo }}</td>
                <td>{{ $cobro->monto }}</td>
                <td>{{ $cobro->detalle }}</td>
                <td>
                    @if($cobro->Contrato_id)
                        <a href="/contrato/{{ $cobro->Contrato_id }}">
                            {{ $cobro->contrato->id ?? $cobro->Contrato_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Servicio_id)
                        <a href="/servicio/{{ $cobro->Servicio_id }}">
                            {{ $cobro->servicio->id ?? $cobro->Servicio_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Propiedad_id)
                        <a href="/propiedad/{{ $cobro->Propiedad_id }}">
                            {{ $cobro->propiedad->id ?? $cobro->Propiedad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->Unidad_id)
                        <a href="/unidad/{{ $cobro->Unidad_id }}">
                            {{ $cobro->unidad->nombre ?? $cobro->Unidad_id }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->deudor)
                        <a href="/cliente/ficha/{{ $cobro->deudor?->cliente?->id }}">
                            {{ $cobro->deudor?->nombre ?? 'Sin nombre' }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($cobro->acreedor)
                        <a href="/cliente/ficha/{{ $cobro->acreedor?->cliente?->id }}">
                            {{ $cobro->acreedor?->nombre ?? 'Sin nombre' }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
    <td>
        <a href="/cobro/{{ $cobro->id }}" class="btn btn-sm btn-outline-primary">Revisar</a>
        <form method="POST" action="/cobro/{{ $cobro->id }}" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('¿Eliminar?')">Eliminar</button>
        </form>
    </td>
</tr>
@endforeach