<div class="row">
    <div class="col-12 mb-3">
        <h2 class="fs-4 mb-0"> Transacciones pendientes </h2>
        <button type="button" class="btn btn-primary btn-sm"
        onclick="abrirModal({titulo: 'Agregar cobro', vista: 'vista-agregar-cobro'})">
            Agregar cobro
        </button>
    </div>
</div>
<div class="row">
    <div class="col-12">
         @if($pendientes->count())
        <div class="table-responsive">
            <table class="table table-hover">
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
    // CUSTOM: filter propiedades for this propiedad context
    // Since we're already on a propiedad, we just use that single propiedad
    $propiedadesPropiedad = collect([$propiedad])->filter();
@endphp
<div class="d-none">
    <div id="vista-agregar-cobro">
        {{-- CUSTOM: hidden inputs for propiedad_id and cliente_id (propietario) — read by JS IIFEs in create modal --}}
        <input type="hidden" id="modal-propiedad-id" value="{{ $propiedad->id }}">
        <input type="hidden" id="modal-cliente-id" value="{{ $propiedad->cliente->id ?? '' }}">
        @include('cobro.modal.create', ['propiedadOptions' => $propiedadesPropiedad, 'propiedadCount' => $propiedadesPropiedad->count(), 'tiposCobroDisponibles' => $tiposCobroDisponibles])
    </div>
</div>