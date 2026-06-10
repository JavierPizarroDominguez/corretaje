@php
    $allCobros = [];
    foreach ($grupo['arrendador'] as $c) {
        $allCobros[] = ['cobro' => $c, 'role' => 'arrendador'];
    }
    foreach ($grupo['arrendatario'] as $c) {
        $allCobros[] = ['cobro' => $c, 'role' => 'arrendatario'];
    }
    foreach ($grupo['corredor'] as $c) {
        $allCobros[] = ['cobro' => $c, 'role' => 'corredor'];
    }
@endphp

{{-- Desktop table (≥576px) --}}
<div class="table-responsive d-none d-sm-block">
    <table class="table table-hover table-card-mobile ficha-pendientes-mobile">
        <thead>
            <tr>
                <th>Concepto</th>
                @if($hasArrendador) <th class="text-center">Arrendador</th> @endif
                @if($hasArrendatario) <th class="text-center">Arrendatario</th> @endif
                @if($hasCorredor) <th class="text-center">Corredor</th> @endif
            </tr>
        </thead>
        <tbody>
            @forelse($allCobros as $item)
                @php
                    $cobro = $item['cobro'];
                    $role = $item['role'];
                    $estado = $cobro['estado'];
                    if ($estado === 'Pendiente') {
                        $colorClass = 'warning';
                    } elseif ($estado === 'Vencido') {
                        $colorClass = 'danger';
                    } else {
                        $colorClass = 'info';
                    }
                    $cobroJson = json_encode($cobro);
                @endphp
                <tr>
                    <td>{{ $cobro['concepto'] }}</td>
                    @if($hasArrendador)
                        <td class="text-center">
                            @if($role === 'arrendador')
                                <button type="button"
                                    class="btn btn-sm btn-{{ $colorClass }} btn-cobro"
                                    data-cobro='{!! $cobroJson !!}'>
                                    {{ $estado }}
                                </button>
                            @endif
                        </td>
                    @endif
                    @if($hasArrendatario)
                        <td class="text-center">
                            @if($role === 'arrendatario')
                                <button type="button"
                                    class="btn btn-sm btn-{{ $colorClass }} btn-cobro"
                                    data-cobro='{!! $cobroJson !!}'>
                                    {{ $estado }}
                                </button>
                            @endif
                        </td>
                    @endif
                    @if($hasCorredor)
                        <td class="text-center">
                            @if($role === 'corredor')
                                <button type="button"
                                    class="btn btn-sm btn-{{ $colorClass }} btn-cobro"
                                    data-cobro='{!! $cobroJson !!}'>
                                    {{ $estado }}
                                </button>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="99" class="text-center text-muted py-3">Sin cobros pendientes</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile cards (<576px): colored estado badges --}}
<div class="d-sm-none">
    @forelse($allCobros as $item)
        @php
            $cobro = $item['cobro'];
            $estado = $cobro['estado'];
            if ($estado === 'Pendiente') {
                $colorClass = 'warning';
            } elseif ($estado === 'Vencido') {
                $colorClass = 'danger';
            } else {
                $colorClass = 'info';
            }
            $cobroJson = json_encode($cobro);
        @endphp
        <div class="mb-2">
            <button type="button"
                class="btn btn-sm btn-{{ $colorClass }} w-100 text-center btn-cobro"
                data-cobro='{!! $cobroJson !!}'>
                {{ $cobro['concepto'] }}
            </button>
        </div>
    @empty
        <div class="text-center text-muted py-2">Sin cobros pendientes</div>
    @endforelse
</div>
