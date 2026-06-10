@forelse($cobros as $cobro)
    @php
        $colorClass = match($cobro['estado'] ?? '') {
            'Pendiente' => 'warning',
            'Vencido' => 'danger',
            default => 'info',
        };
    @endphp
    <div class="mb-1">
        <button type="button"
            class="btn btn-sm btn-{{ $colorClass }} w-100 text-center btn-cobro"
            data-cobro='@json($cobro)'>
            {{ $cobro['concepto'] ?? 'Sin tipo' }}
        </button>
    </div>
@empty
    <span class="text-muted">—</span>
@endforelse
