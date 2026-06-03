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
<div class="collapse" id="filter-panel-cliente">
    <div class="card card-body bg-light border p-3">
                    <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-date-cliente"
                     role="button"
                     aria-expanded="true"
                     aria-controls="fs-date-cliente">
                    <span class="filter-group-icon">📅</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por fechas</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse show" id="fs-date-cliente">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-6">
    <label class="form-label small text-secondary mb-1">Fecha Creacion</label>
    <div class="d-flex gap-1 mb-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_creacion" data-days="0">Hoy</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_creacion" data-days="7">7 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_creacion" data-days="30">30 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_creacion" data-days="90">90 días</button>
    </div>
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <select name="filter[fecha_creacion_month]"
                class="form-select form-select-sm"
                style="width:auto;min-width:100px;"
                data-filter="fecha_creacion_month">
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
        <select name="filter[fecha_creacion_year]"
                class="form-select form-select-sm"
                style="width:auto;min-width:90px;"
                data-filter="fecha_creacion_year">
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
               name="filter[fecha_creacion_from]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Desde"
               data-filter="fecha_creacion_from">
        <span class="small text-muted">→</span>
        <input type="date"
               name="filter[fecha_creacion_to]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Hasta"
               data-filter="fecha_creacion_to">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-text-cliente"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-text-cliente">
                    <span class="filter-group-icon">📝</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por texto</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-text-cliente">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Nombre</label>
    <input type="text"
           name="filter[nombre]"
           class="form-control form-control-sm"
           value=""
           placeholder="Nombre..."
           data-filter="nombre"
           autocomplete="off">
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Rut</label>
    <input type="text"
           name="filter[rut]"
           class="form-control form-control-sm"
           value=""
           placeholder="Rut..."
           data-filter="rut"
           autocomplete="off">
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Email</label>
    <input type="text"
           name="filter[email]"
           class="form-control form-control-sm"
           value=""
           placeholder="Email..."
           data-filter="email"
           autocomplete="off">
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Ocupacion</label>
    <input type="text"
           name="filter[ocupacion]"
           class="form-control form-control-sm"
           value=""
           placeholder="Ocupacion..."
           data-filter="ocupacion"
           autocomplete="off">
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-fk-cliente"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-fk-cliente">
                    <span class="filter-group-icon">🔗</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por relaciones</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-fk-cliente">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Nacionalidad</label>
    <select name="filter[Nacionalidad_id]"
            class="form-select form-select-sm"
            data-filter="Nacionalidad_id">
        <option value="">Todos</option>
        @php($nacionalidadOptions = \App\Models\Nacionalidad::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($nacionalidadOptions as $opt)
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
                     data-bs-target="#fs-enum-0-cliente"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-enum-0-cliente">
                    <span class="filter-group-icon">🏷️</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Estado Civil</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-enum-0-cliente">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-4">
    <label class="form-label small text-secondary mb-1">Estado Civil</label>
    <div class="d-flex flex-wrap gap-1">
            <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado_civil][]"
               value="Soltero"
               id="filter-estado_civil-Soltero"
               data-filter="estado_civil">
        <label class="form-check-label small"
               for="filter-estado_civil-Soltero">Soltero</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado_civil][]"
               value="Casado"
               id="filter-estado_civil-Casado"
               data-filter="estado_civil">
        <label class="form-check-label small"
               for="filter-estado_civil-Casado">Casado</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado_civil][]"
               value="Viudo"
               id="filter-estado_civil-Viudo"
               data-filter="estado_civil">
        <label class="form-check-label small"
               for="filter-estado_civil-Viudo">Viudo</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado_civil][]"
               value="Divorciado"
               id="filter-estado_civil-Divorciado"
               data-filter="estado_civil">
        <label class="form-check-label small"
               for="filter-estado_civil-Divorciado">Divorciado</label>
    </div>
    </div>
</div>
                    </div>
                </div>
            </div>
        <div class="row mt-3 pt-2 border-top">
            <div class="col-12 d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="btn-apply-filters-cliente">
                    <i class="ti ti-filter"></i> Aplicar filtros
                </button>
                <a href="/cliente" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-x"></i> Limpiar
                </a>
                <small class="text-muted ms-auto" id="filter-total-cliente"></small>
            </div>
        </div>
    </div>
</div>