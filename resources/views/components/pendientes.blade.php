<div class="row">
    <div class="col-12 mb-3">
        <h2 class="fs-4 mb-0"> Transacciones pendientes </h2>
        <button type="button" class="btn btn-primary btn-sm"
        onclick="abrirModal({titulo: 'Agregar cobro', vista: 'vista-agregar-cobro', cliente_id: {{ $cliente->id }}})">
            Agregar cobro
        </button>
    </div>
</div>
<div id="pendientes-section">
    @php
        $hasArrendador = collect($groupedPendientes)->contains(fn ($grupo) => count($grupo['arrendador'] ?? []) > 0);
        $hasArrendatario = collect($groupedPendientes)->contains(fn ($grupo) => count($grupo['arrendatario'] ?? []) > 0);
        $hasCorredor = collect($groupedPendientes)->contains(fn ($grupo) => count($grupo['corredor'] ?? []) > 0);
        $roleColumnCount = ($hasArrendador ? 1 : 0) + ($hasArrendatario ? 1 : 0) + ($hasCorredor ? 1 : 0);
    @endphp

    @if(count($groupedPendientes))
        <div class="card" id="ficha-pendientes-container">
            <div class="table-responsive" id="ficha-pendientes-wrapper">
                <table class="table mb-0 text-nowrap table-hover table-card-mobile pendientes-dashboard-table ficha-pendientes-table">
                    <thead class="table-light border-light">
                        <tr>
                            <th><b>Dirección</b></th>
                            @if($hasArrendador)<th data-col="arrendador"><b>Cobros al Arrendador</b></th>@endif
                            @if($hasArrendatario)<th data-col="arrendatario"><b>Cobros al Arrendatario</b></th>@endif
                            @if($hasCorredor)<th data-col="corredor"><b>Cobros al Corredor</b></th>@endif
                        </tr>
                    </thead>
                    <tbody id="body-ficha-pendientes">
                        @foreach($groupedPendientes as $grupo)
                            <tr>
                                <td><a href="/propiedad/ficha/{{ $grupo['id'] }}">{{ $grupo['direccion'] }}</a></td>
                                @if($hasArrendador)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $grupo['arrendador'] ?? []])</td>@endif
                                @if($hasArrendatario)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $grupo['arrendatario'] ?? []])</td>@endif
                                @if($hasCorredor)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $grupo['corredor'] ?? []])</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                    @if(($pendientesPaginator ?? $pendientes)->hasPages())
                        <tfoot>
                            <tr>
                                <td colspan="{{ max(1, $roleColumnCount + 1) }}" class="border-bottom-0">
                                    {{ ($pendientesPaginator ?? $pendientes)->links() }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-light border">
            No hay transacciones pendientes por el momento.
        </div>
    @endif
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
