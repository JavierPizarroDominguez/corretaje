<style>
    .filter-group-header {
        background: #f8f9fa;
        border-radius: 4px;
        transition: background 0.15s ease;
        user-select: none;
        cursor: pointer;
    }
    .filter-group-header:hover {
        background: #e9ecef;
    }
    .filter-group-header[aria-expanded="true"] .filter-chevron {
        transform: rotate(180deg);
    }
    .filter-chevron {
        transition: transform 0.2s ease;
        font-size: 0.65rem;
        color: #adb5bd;
    }
    .filter-group-title {
        letter-spacing: 0.03em;
        font-size: 0.75rem;
    }
    .filter-group-icon {
        font-size: 0.85rem;
        line-height: 1;
    }
</style>
<div class="collapse" id="filter-panel-servicio">
    <div class="card card-body bg-light border p-3">
                    <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-number-servicio"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-number-servicio">
                    <span class="filter-group-icon">💰</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por montos</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-number-servicio">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Dia Pago</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[dia_pago_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="dia_pago_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[dia_pago_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="dia_pago_max">
    </div>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Monto Fijo</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[monto_fijo_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="monto_fijo_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[monto_fijo_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="monto_fijo_max">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-text-servicio"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-text-servicio">
                    <span class="filter-group-icon">📝</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por texto</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-text-servicio">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Numero Cliente</label>
    <input type="text"
           name="filter[numero_cliente]"
           class="form-control form-control-sm"
           value=""
           placeholder="Numero Cliente..."
           data-filter="numero_cliente"
           autocomplete="off">
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-fk-servicio"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-fk-servicio">
                    <span class="filter-group-icon">🔗</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por relaciones</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-fk-servicio">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Propiedad</label>
    <select name="filter[Propiedad_id]"
            class="form-select form-select-sm"
            data-filter="Propiedad_id">
        <option value="">Todos</option>
        @php($propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion as display']))
        @foreach($propiedadOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Empresa</label>
    <select name="filter[Empresa_id]"
            class="form-select form-select-sm"
            data-filter="Empresa_id">
        <option value="">Todos</option>
        @php($empresaOptions = \App\Models\Empresa::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($empresaOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-enum-0-servicio"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-enum-0-servicio">
                    <span class="filter-group-icon">🏷️</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Tipo</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-enum-0-servicio">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-4">
    <label class="form-label small text-secondary mb-1">Tipo</label>
    <div class="d-flex flex-wrap gap-1">
            <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="Luz"
               id="filter-tipo-Luz"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-Luz">Luz</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="Agua"
               id="filter-tipo-Agua"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-Agua">Agua</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="Gas"
               id="filter-tipo-Gas"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-Gas">Gas</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="Gastos Comunes"
               id="filter-tipo-Gastos_Comunes"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-Gastos_Comunes">Gastos Comunes</label>
    </div>
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-enum-1-servicio"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-enum-1-servicio">
                    <span class="filter-group-icon">🏷️</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Estado</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-enum-1-servicio">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Estado</label>
    <div class="d-flex flex-wrap gap-1">
            <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="Activo"
               id="filter-estado-Activo"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-Activo">Activo</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="Inactivo"
               id="filter-estado-Inactivo"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-Inactivo">Inactivo</label>
    </div>
    </div>
</div>
                    </div>
                </div>
            </div>
        <div class="row mt-3 pt-2 border-top">
            <div class="col-12 d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="btn-apply-filters-servicio">
                    <i class="ti ti-filter"></i> Aplicar filtros
                </button>
                <a href="/servicio" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-x"></i> Limpiar
                </a>
                <small class="text-muted ms-auto" id="filter-total-servicio"></small>
            </div>
        </div>
    </div>
</div>