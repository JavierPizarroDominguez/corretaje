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
<div class="collapse" id="filter-panel-contrato">
    <div class="card card-body bg-light border p-3">
                    <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-date-contrato"
                     role="button"
                     aria-expanded="true"
                     aria-controls="fs-date-contrato">
                    <span class="filter-group-icon">📅</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por fechas</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse show" id="fs-date-contrato">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-6">
    <label class="form-label small text-secondary mb-1">Fecha Firma</label>
    <div class="d-flex gap-1 mb-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_firma" data-days="0">Hoy</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_firma" data-days="7">7 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_firma" data-days="30">30 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_firma" data-days="90">90 días</button>
    </div>
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <select name="filter[fecha_firma_month]"
                class="form-select form-select-sm"
                style="width:auto;min-width:100px;"
                data-filter="fecha_firma_month">
            <option value="">Mes</option>
                    <option value="1">Enero</option>
        <option value="2">Febrero</option>
        <option value="3">Marzo</option>
        <option value="4">Abril</option>
        <option value="5">Mayo</option>
        <option value="6">Junio</option>
        <option value="7">Julio</option>
        <option value="8">Agosto</option>
        <option value="9">Septiembre</option>
        <option value="10">Octubre</option>
        <option value="11">Noviembre</option>
        <option value="12">Diciembre</option>
        </select>
        <select name="filter[fecha_firma_year]"
                class="form-select form-select-sm"
                style="width:auto;min-width:90px;"
                data-filter="fecha_firma_year">
            <option value="">Año</option>
                    <option value="2026">2026</option>
        <option value="2025">2025</option>
        <option value="2024">2024</option>
        <option value="2023">2023</option>
        <option value="2022">2022</option>
        <option value="2021">2021</option>
        <option value="2020">2020</option>
        <option value="2019">2019</option>
        <option value="2018">2018</option>
        <option value="2017">2017</option>
        <option value="2016">2016</option>
        </select>
        <input type="date"
               name="filter[fecha_firma_from]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Desde"
               data-filter="fecha_firma_from">
        <span class="small text-muted">→</span>
        <input type="date"
               name="filter[fecha_firma_to]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Hasta"
               data-filter="fecha_firma_to">
    </div>
</div>
<div class="mb-2 col-md-6">
    <label class="form-label small text-secondary mb-1">Fecha Inicio</label>
    <div class="d-flex gap-1 mb-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_inicio" data-days="0">Hoy</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_inicio" data-days="7">7 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_inicio" data-days="30">30 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_inicio" data-days="90">90 días</button>
    </div>
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <select name="filter[fecha_inicio_month]"
                class="form-select form-select-sm"
                style="width:auto;min-width:100px;"
                data-filter="fecha_inicio_month">
            <option value="">Mes</option>
                    <option value="1">Enero</option>
        <option value="2">Febrero</option>
        <option value="3">Marzo</option>
        <option value="4">Abril</option>
        <option value="5">Mayo</option>
        <option value="6">Junio</option>
        <option value="7">Julio</option>
        <option value="8">Agosto</option>
        <option value="9">Septiembre</option>
        <option value="10">Octubre</option>
        <option value="11">Noviembre</option>
        <option value="12">Diciembre</option>
        </select>
        <select name="filter[fecha_inicio_year]"
                class="form-select form-select-sm"
                style="width:auto;min-width:90px;"
                data-filter="fecha_inicio_year">
            <option value="">Año</option>
                    <option value="2026">2026</option>
        <option value="2025">2025</option>
        <option value="2024">2024</option>
        <option value="2023">2023</option>
        <option value="2022">2022</option>
        <option value="2021">2021</option>
        <option value="2020">2020</option>
        <option value="2019">2019</option>
        <option value="2018">2018</option>
        <option value="2017">2017</option>
        <option value="2016">2016</option>
        </select>
        <input type="date"
               name="filter[fecha_inicio_from]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Desde"
               data-filter="fecha_inicio_from">
        <span class="small text-muted">→</span>
        <input type="date"
               name="filter[fecha_inicio_to]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Hasta"
               data-filter="fecha_inicio_to">
    </div>
</div>
<div class="mb-2 col-md-6">
    <label class="form-label small text-secondary mb-1">Fecha Termino</label>
    <div class="d-flex gap-1 mb-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_termino" data-days="0">Hoy</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_termino" data-days="7">7 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_termino" data-days="30">30 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_termino" data-days="90">90 días</button>
    </div>
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <select name="filter[fecha_termino_month]"
                class="form-select form-select-sm"
                style="width:auto;min-width:100px;"
                data-filter="fecha_termino_month">
            <option value="">Mes</option>
                    <option value="1">Enero</option>
        <option value="2">Febrero</option>
        <option value="3">Marzo</option>
        <option value="4">Abril</option>
        <option value="5">Mayo</option>
        <option value="6">Junio</option>
        <option value="7">Julio</option>
        <option value="8">Agosto</option>
        <option value="9">Septiembre</option>
        <option value="10">Octubre</option>
        <option value="11">Noviembre</option>
        <option value="12">Diciembre</option>
        </select>
        <select name="filter[fecha_termino_year]"
                class="form-select form-select-sm"
                style="width:auto;min-width:90px;"
                data-filter="fecha_termino_year">
            <option value="">Año</option>
                    <option value="2026">2026</option>
        <option value="2025">2025</option>
        <option value="2024">2024</option>
        <option value="2023">2023</option>
        <option value="2022">2022</option>
        <option value="2021">2021</option>
        <option value="2020">2020</option>
        <option value="2019">2019</option>
        <option value="2018">2018</option>
        <option value="2017">2017</option>
        <option value="2016">2016</option>
        </select>
        <input type="date"
               name="filter[fecha_termino_from]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Desde"
               data-filter="fecha_termino_from">
        <span class="small text-muted">→</span>
        <input type="date"
               name="filter[fecha_termino_to]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Hasta"
               data-filter="fecha_termino_to">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-number-contrato"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-number-contrato">
                    <span class="filter-group-icon">💰</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por montos</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-number-contrato">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Comision Inicial</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[comision_inicial_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="comision_inicial_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[comision_inicial_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="comision_inicial_max">
    </div>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Garantia</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[garantia_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="garantia_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[garantia_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="garantia_max">
    </div>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Renta</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[renta_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="renta_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[renta_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="renta_max">
    </div>
</div>
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
    <label class="form-label small text-secondary mb-1">Comision Mensual</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[comision_mensual_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="comision_mensual_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[comision_mensual_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="comision_mensual_max">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-text-contrato"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-text-contrato">
                    <span class="filter-group-icon">📝</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por texto</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-text-contrato">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Url Pdf</label>
    <input type="text"
           name="filter[url_pdf]"
           class="form-control form-control-sm"
           value=""
           placeholder="Url Pdf..."
           data-filter="url_pdf"
           autocomplete="off">
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-fk-contrato"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-fk-contrato">
                    <span class="filter-group-icon">🔗</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por relaciones</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-fk-contrato">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Unidad</label>
    <select name="filter[Unidad_id]"
            class="form-select form-select-sm"
            data-filter="Unidad_id">
        <option value="">Todos</option>
        @php($unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($unidadOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Ciudad</label>
    <select name="filter[Ciudad_id]"
            class="form-select form-select-sm"
            data-filter="Ciudad_id">
        <option value="">Todos</option>
        @php($ciudadOptions = \App\Models\Ciudad::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($ciudadOptions as $opt)
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
                     data-bs-target="#fs-boolean-contrato"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-boolean-contrato">
                    <span class="filter-group-icon">✅</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por opciones</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-boolean-contrato">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Administracion</label>
    <select name="filter[administracion]"
            class="form-select form-select-sm"
            data-filter="administracion">
        <option value="">Todos</option>
        <option value="1">Sí</option>
        <option value="0">No</option>
    </select>
</div>
                    </div>
                </div>
            </div>
        <div class="row mt-3 pt-2 border-top">
            <div class="col-12 d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="btn-apply-filters-contrato">
                    <i class="bi bi-funnel"></i> Aplicar filtros
                </button>
                <a href="/contrato" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <small class="text-muted ms-auto" id="filter-total-contrato"></small>
            </div>
        </div>
    </div>
</div>