<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1>{{ $propiedad->direccion }}</h1>
        <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="abrirModal({titulo: 'Propiedad', vista: 'vista-propiedad-{{ $propiedad->id }}'})">
            Ver detalle
        </button>
    </div>
</div>
<div class="d-none">
    <div id="vista-propiedad-{{ $propiedad->id }}">
        @include('propiedad.modal.show', ['propiedad' => $propiedad, 'clienteCount' => $clienteCount ?? 0, 'clienteOptions' => $clienteOptions ?? collect()])
    </div>
</div>