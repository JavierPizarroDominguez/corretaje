@php
    $contratosSinTermino = ($contratosVigentes ?? collect())->filter(fn ($contrato) => is_null($contrato->fecha_termino));
    $estaArrendada = $contratosSinTermino->isNotEmpty();
@endphp

<div class="row mb-3">
    <div class="col-12">
        <h1 class="h3 mb-3">Ficha de propiedad</h1>
        <div class="row g-3 propiedad-summary-cards">
            <div class="col-md-5">
                <div class="card propiedad-summary-card h-100">
                    <div class="card-body">
                        <div class="propiedad-summary-label">Dirección</div>
                        <div class="propiedad-summary-value">{{ $propiedad->direccion }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card propiedad-summary-card h-100">
                    <div class="card-body">
                        <div class="propiedad-summary-label">Propietario</div>
                        @if($propiedad->cliente)
                            <a href="/cliente/{{ $propiedad->cliente->id }}" class="propiedad-summary-value text-decoration-none">
                                {{ $propiedad->cliente->nombre }}
                            </a>
                        @else
                            <span class="text-muted fst-italic">Sin propietario</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card propiedad-summary-card h-100">
                    <div class="card-body">
                        <div class="propiedad-summary-label">Estado</div>
                        <div>
                            <span class="badge propiedad-status-badge {{ $estaArrendada ? 'bg-success' : 'bg-secondary' }}">
                                {{ $estaArrendada ? 'Arrendada' : 'Desocupada' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
