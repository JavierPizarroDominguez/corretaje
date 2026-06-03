@if($transacciones->count())
<div class="row mt-4">
    <div class="col-12 mb-3">
        <h2 class="fs-4 mb-0">
            Historial de transacciones
        </h2>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Detalle</th>
                        <th>Deudor</th>
                        <th>Acreedor</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transacciones as $transaccion)
                        @php
                            $cobro = $transaccion->cobros->first();
                        @endphp
                        <tr>
                            <td>
                                @if($transaccion->fecha)
                                    {{ $transaccion->fecha->day }}
                                    de
                                    {{ mb_convert_case($transaccion->fecha->translatedFormat('F'), MB_CASE_TITLE, 'UTF-8') }}
                                    {{ $transaccion->fecha->year }}
                                @endif
                            </td>
                            <td>{{ $cobro?->tipo ?? '—' }}</td>
                            <td>${{ number_format($transaccion->monto, 0, ',', '.') }}
                            </td>
                            <td>{{ $cobro?->detalle ?? '—' }}</td>
                            <td>
                                @if($cobro?->deudor)
                                        {{ $cobro->deudor?->nombre }}

                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($cobro?->acreedor)
                                        {{ $cobro->acreedor?->nombre }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge bg-success">Pagado</span></td>
                            <td>Ver</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $transacciones->links() }}
        </div>
    </div>
</div>
@endif