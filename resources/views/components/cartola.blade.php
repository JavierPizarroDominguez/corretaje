@if(!empty($cartola))

    @foreach($cartola as $unidad => $years)

        <div class="mb-5">

            <h2 class="mb-3">
                Cartola Unidad #{{ $unidad }}
            </h2>

            @foreach($years as $year => $meses)

                <h4 class="mb-3">
                    Año {{ $year }}
                </h4>

                <div class="table-responsive mb-4">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Mes</th>
                                @foreach($columnasCartola as $columna)
                                    <th>{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($meses as $mes => $fila)

                                <tr>

                                    <td class="fw-semibold">
                                        {{ $mes }}
                                    </td>

                                    @foreach($columnasCartola as $columna)

                                        <td>

                                            @php
                                                $cobro = $fila[$columna] ?? null;
                                            @endphp

                                            @if($cobro)

                                                @php
                                                    $badge = match($cobro->estado) {
                                                        'Pagado' => 'success',
                                                        'Pendiente' => 'warning',
                                                        'Vencido' => 'danger',
                                                        'Incompleto' => 'secondary',
                                                        default => 'secondary',
                                                    };
                                                @endphp

                                                <span class="badge bg-{{ $badge }}">
                                                    {{ $cobro->estado }}
                                                </span>

                                            @else
                                                <span class="text-muted">—</span>
                                            @endif

                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endforeach

        </div>

    @endforeach

@endif