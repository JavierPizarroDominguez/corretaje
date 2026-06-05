<div class="row">
    <div class="col-12 mb-3">
        <h2 class="fs-4 mb-0"> Transacciones pendientes </h2>
        <button type="button" class="btn btn-primary btn-sm"
        onclick="abrirModal({titulo: 'Agregar cobro', vista: 'vista-agregar-cobro', cliente_id: {{ $cliente->id }}})">
            Agregar cobro
        </button>
    </div>
</div>
<div class="row">
    <div class="col-12">
         @if($pendientes->count())
        <div class="table-responsive">
            <table class="table table-hover table-card-mobile">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $cobro)
                        <tr>
                            <td>
                                {{ $cobro->concepto }}
                            </td>
                            <td>
            <button type="button" class="btn btn-primary btn-sm" 
            onclick="abrirModal({titulo: '{{ $cobro->concepto }}', vista: 'vista-revisar-cobro-{{ $cobro->id }}'})">
                Revisar
            </button>

            <div class="d-none">
                <div id="vista-revisar-cobro-{{ $cobro->id }}">
                    @include('cobro.modal.show', ['cobro' => $cobro])
                </div>
            </div>
        </td>
    </tr>
@endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $pendientes->links() }}
        </div>
         @else
            <div class="alert alert-light border">
                No hay transacciones pendientes por el momento.
            </div>
        @endif
    </div>
</div>
@php
    // CUSTOM: filter properties for this cliente
    $propiedadesCliente = collect();
    foreach($cliente->propiedades ?? collect() as $p) {
        $propiedadesCliente->push($p);
    }
    foreach($cliente->participante_contratos ?? collect() as $pc) {
        if($pc->contrato && $pc->contrato->unidad && $pc->contrato->unidad->propiedad) {
            $propiedadesCliente->push($pc->contrato->unidad->propiedad);
        }
    }
    $propiedadesCliente = $propiedadesCliente->unique('id')->sortBy('direccion');
@endphp
<div class="d-none">
    <div id="vista-agregar-cobro">
        {{-- CUSTOM: hidden input for cliente_id — populated by abrirModal via cliente_id param --}}
        <input type="hidden" id="modal-cliente-id" value="{{ $cliente->id }}">
        @include('cobro.modal.create', ['cliente' => $cliente, 'propiedadOptions' => $propiedadesCliente, 'propiedadCount' => $propiedadesCliente->count(), 'clienteOptions' => $clienteOptions, 'tiposCobroDisponibles' => $tiposCobroDisponibles])
    </div>
</div>