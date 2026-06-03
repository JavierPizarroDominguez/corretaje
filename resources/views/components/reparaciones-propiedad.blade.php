{{-- resources/views/components/reparaciones.blade.php --}}

<div class="row">
    <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
        <h2 class="fs-4 mb-0">Reparaciones y gastos extras</h2>
        <a  href="#"  class="btn btn-primary btn-sm">Agregar cobro</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @if($reparaciones->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Concepto</th>
                            <th>Detalle</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Deudor</th>
                            <th>Acreedor</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reparaciones as $cobro)
                            <tr>
                                <td>{{ $cobro->tipo }}</td>
                                <td>{{ $cobro->detalle ?: '—' }} </td>
                                <td>${{ number_format($cobro->monto, 0, ',', '.') }}</td>
                                <td>{{ucfirst($cobro->fecha_cobro?->translatedFormat('j \d\e F Y'))}}</td>
                                <td>
                                    @if($cobro->deudor?->cliente)
                                        <a href="#">{{ $cobro->deudor?->cliente?->nombre }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($cobro->acreedor?->cliente)
                                        <a href="#">{{ $cobro->acreedor?->cliente?->nombre }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match($cobro->estado) {

                                            'Pagado' => 'success',
                                            'Pendiente' => 'warning',
                                            'Vencido' => 'danger',
                                            'Incompleto' => 'secondary',
                                            'Anulado' => 'dark',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">
                                        {{ $cobro->estado }}
                                    </span>
                                </td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Revisar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $reparaciones->links() }}
            </div>
        @else
            <div class="alert alert-light border">
                No se han registrado reparaciones ni gastos extra.
            </div>
        @endif
    </div>
</div>