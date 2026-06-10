<div class="row">
    <div class="col-12 mb-3">
        <h2 class="fs-4 mb-0"> Transacciones pendientes </h2>
        <button type="button" class="btn btn-primary btn-sm"
        onclick="abrirModal({titulo: 'Agregar cobro', vista: 'vista-agregar-cobro'})">
            Agregar cobro
        </button>
    </div>
</div>
<div id="pendientes-section">
    @php
        $showUnidadColumn = $showUnidadColumn ?? false;
        $hasArrendador = collect($groupedPendientes)->contains(fn ($unidad) => count($unidad['arrendador'] ?? []) > 0);
        $hasArrendatario = collect($groupedPendientes)->contains(fn ($unidad) => count($unidad['arrendatario'] ?? []) > 0);
        $hasCorredor = collect($groupedPendientes)->contains(fn ($unidad) => count($unidad['corredor'] ?? []) > 0);
        $descriptorColumnCount = $showUnidadColumn ? 1 : 0;
        $roleColumnCount = ($hasArrendador ? 1 : 0) + ($hasArrendatario ? 1 : 0) + ($hasCorredor ? 1 : 0);
    @endphp

    @if(count($groupedPendientes))
        <div class="card" id="ficha-pendientes-container">
            <div class="table-responsive" id="ficha-pendientes-wrapper">
                <table class="table mb-0 text-nowrap table-hover table-card-mobile pendientes-dashboard-table ficha-pendientes-table">
                    <thead class="table-light border-light">
                        <tr>
                            @if($showUnidadColumn)<th><b>Unidad</b></th>@endif
                            @if($hasArrendador)<th data-col="arrendador"><b>Cobros al Arrendador</b></th>@endif
                            @if($hasArrendatario)<th data-col="arrendatario"><b>Cobros al Arrendatario</b></th>@endif
                            @if($hasCorredor)<th data-col="corredor"><b>Cobros al Corredor</b></th>@endif
                        </tr>
                    </thead>
                    <tbody id="body-ficha-pendientes">
                        @foreach($groupedPendientes as $unidad)
                            <tr>
                                @if($showUnidadColumn)<td>{{ $unidad['nombre'] }}</td>@endif
                                @if($hasArrendador)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $unidad['arrendador'] ?? []])</td>@endif
                                @if($hasArrendatario)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $unidad['arrendatario'] ?? []])</td>@endif
                                @if($hasCorredor)<td class="td-cobros">@include('components._pendientes-cobros-buttons', ['cobros' => $unidad['corredor'] ?? []])</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                    @if(($pendientesPaginator ?? $pendientes)->hasPages())
                        <tfoot>
                            <tr>
                                <td colspan="{{ max(1, $descriptorColumnCount + $roleColumnCount) }}" class="border-bottom-0">
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
    // CUSTOM: filter propiedades for this propiedad context
    // Since we're already on a propiedad, we just use that single propiedad
    $propiedadesPropiedad = collect([$propiedad])->filter();
@endphp
<div class="d-none">
    <div id="vista-agregar-cobro">
        {{-- CUSTOM: hidden inputs for propiedad_id and cliente_id (propietario) — read by JS IIFEs in create modal --}}
        <input type="hidden" id="modal-propiedad-id" value="{{ $propiedad->id }}">
        <input type="hidden" id="modal-cliente-id" value="{{ $propiedad->cliente->id ?? '' }}">
        @include('cobro.modal.create', [
            'propiedadOptions' => $propiedadesPropiedad,
            'propiedadCount' => $propiedadesPropiedad->count(),
            'tiposCobroDisponibles' => $tiposCobroDisponibles,
            'fichaContext' => true,
            'participantOptions' => $participantOptions ?? collect(),
        ])
    </div>
</div>
