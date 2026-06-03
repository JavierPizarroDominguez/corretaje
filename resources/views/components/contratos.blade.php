@foreach($contratosVigentes as $contrato)

    <div class="card mb-4">

        <div class="card-header">
            <h5>
                Contrato —
                {{ $contrato->unidad->propiedad->direccion ?? 'Sin propiedad' }}
            </h5>
        </div>

        <div class="card-body">

            <table class="table table-bordered">

                <tr>
                    <td><b>Renta</b></td>
                    <td>{{ $contrato->renta }}</td>
                </tr>

                <tr>
                    <td><b>Garantía</b></td>
                    <td>{{ $contrato->garantia }}</td>
                </tr>

                <tr>
                    <td><b>Fecha Inicio</b></td>
                    <td>{{ $contrato->fecha_inicio }}</td>
                </tr>

                <tr>
                    <td><b>Fecha Término</b></td>
                    <td>{{ $contrato->fecha_termino ?? 'Indefinido' }}</td>
                </tr>

                <tr>
                    <td><b>Arrendador</b></td>
                    <td>
                        @if($contrato->arrendador)
                            <a href="/cliente/{{ $contrato->arrendador->id }}">
                                {{ $contrato->arrendador->nombre }}
                            </a>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td><b>Arrendatario</b></td>
                    <td>
                        @if($contrato->arrendatario)
                            <a href="/cliente/{{ $contrato->arrendatario->id }}">
                                {{ $contrato->arrendatario->nombre }}
                            </a>
                        @endif
                    </td>
                </tr>

            </table>

        </div>

    </div>

@endforeach