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
<div class="collapse" id="filter-panel-cobro">
    <div class="card card-body bg-light border p-3">
                    <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-date-cobro"
                     role="button"
                     aria-expanded="true"
                     aria-controls="fs-date-cobro">
                    <span class="filter-group-icon">📅</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por fechas</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse show" id="fs-date-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-6">
    <label class="form-label small text-secondary mb-1">Fecha Cobro</label>
    <div class="d-flex gap-1 mb-1 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_cobro" data-days="0">Hoy</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_cobro" data-days="7">7 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_cobro" data-days="30">30 días</button>
        <button type="button" class="btn btn-outline-secondary btn-sm date-preset"
                data-field="fecha_cobro" data-days="90">90 días</button>
    </div>
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <select name="filter[fecha_cobro_month]"
                class="form-select form-select-sm"
                style="width:auto;min-width:100px;"
                data-filter="fecha_cobro_month">
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
        <select name="filter[fecha_cobro_year]"
                class="form-select form-select-sm"
                style="width:auto;min-width:90px;"
                data-filter="fecha_cobro_year">
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
               name="filter[fecha_cobro_from]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Desde"
               data-filter="fecha_cobro_from">
        <span class="small text-muted">→</span>
        <input type="date"
               name="filter[fecha_cobro_to]"
               class="form-control form-control-sm"
               style="width:auto;min-width:140px;"
               value=""
               placeholder="Hasta"
               data-filter="fecha_cobro_to">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-number-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-number-cobro">
                    <span class="filter-group-icon">💰</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por montos</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-number-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Monto</label>
    <div class="input-group input-group-sm">
        <input type="number"
               name="filter[monto_min]"
               class="form-control"
               value=""
               placeholder="Mayor a"
               step="any"
               data-filter="monto_min">
        <span class="input-group-text">—</span>
        <input type="number"
               name="filter[monto_max]"
               class="form-control"
               value=""
               placeholder="Menor a"
               step="any"
               data-filter="monto_max">
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-text-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-text-cobro">
                    <span class="filter-group-icon">📝</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por texto</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-text-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Detalle</label>
    <input type="text"
           name="filter[detalle]"
           class="form-control form-control-sm"
           value=""
           placeholder="Detalle..."
           data-filter="detalle"
           autocomplete="off">
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-fk-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-fk-cobro">
                    <span class="filter-group-icon">🔗</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por relaciones</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-fk-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Contrato</label>
    <select name="filter[Contrato_id]"
            class="form-select form-select-sm"
            data-filter="Contrato_id">
        <option value="">Todos</option>
        @php($contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id as display']))
        @foreach($contratoOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Servicio</label>
    <select name="filter[Servicio_id]"
            class="form-select form-select-sm"
            data-filter="Servicio_id">
        <option value="">Todos</option>
        @php($servicioOptions = \App\Models\Servicio::orderBy('id')->get(['id', 'id as display']))
        @foreach($servicioOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
<div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Propiedad</label>
    <select name="filter[Propiedad_id]"
            class="form-select form-select-sm"
            data-filter="Propiedad_id">
        <option value="">Todos</option>
        @php($propiedadOptions = \App\Models\Propiedad::orderBy('id')->get(['id', 'id as display']))
        @foreach($propiedadOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
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
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-enum-0-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-enum-0-cobro">
                    <span class="filter-group-icon">🏷️</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Estado</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-enum-0-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-4">
    <label class="form-label small text-secondary mb-1">Estado</label>
    <div class="d-flex flex-wrap gap-1">
            <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="pagado"
               id="filter-estado-pagado"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-pagado">pagado</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="incompleto"
               id="filter-estado-incompleto"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-incompleto">incompleto</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="pendiente"
               id="filter-estado-pendiente"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-pendiente">pendiente</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="vencido"
               id="filter-estado-vencido"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-vencido">vencido</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[estado][]"
               value="anulado"
               id="filter-estado-anulado"
               data-filter="estado">
        <label class="form-check-label small"
               for="filter-estado-anulado">anulado</label>
    </div>
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-enum-1-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-enum-1-cobro">
                    <span class="filter-group-icon">🏷️</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Tipo</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-enum-1-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-4">
    <label class="form-label small text-secondary mb-1">Tipo</label>
    <div class="d-flex flex-wrap gap-1">
            <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="ingreso renta arrendatario"
               id="filter-tipo-ingreso_renta_arrendatario"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-ingreso_renta_arrendatario">ingreso renta arrendatario</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="egreso renta arrendador"
               id="filter-tipo-egreso_renta_arrendador"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-egreso_renta_arrendador">egreso renta arrendador</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="comision inicial arrendador"
               id="filter-tipo-comision_inicial_arrendador"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-comision_inicial_arrendador">comision inicial arrendador</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="comision inicial arrendatario"
               id="filter-tipo-comision_inicial_arrendatario"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-comision_inicial_arrendatario">comision inicial arrendatario</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="comision mensual"
               id="filter-tipo-comision_mensual"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-comision_mensual">comision mensual</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="ingreso garantía arrendatario"
               id="filter-tipo-ingreso_garant__a_arrendatario"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-ingreso_garant__a_arrendatario">ingreso garantía arrendatario</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="egreso garantía arrendador"
               id="filter-tipo-egreso_garant__a_arrendador"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-egreso_garant__a_arrendador">egreso garantía arrendador</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="devolución garantía arrendatario"
               id="filter-tipo-devoluci__n_garant__a_arrendatario"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-devoluci__n_garant__a_arrendatario">devolución garantía arrendatario</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="aseo final"
               id="filter-tipo-aseo_final"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-aseo_final">aseo final</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="luz"
               id="filter-tipo-luz"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-luz">luz</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="agua"
               id="filter-tipo-agua"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-agua">agua</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="gas"
               id="filter-tipo-gas"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-gas">gas</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="gastos comunes"
               id="filter-tipo-gastos_comunes"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-gastos_comunes">gastos comunes</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="reparación"
               id="filter-tipo-reparaci__n"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-reparaci__n">reparación</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="extra"
               id="filter-tipo-extra"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-extra">extra</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input filter-enum-cb"
               type="checkbox"
               name="filter[tipo][]"
               value="devolución"
               id="filter-tipo-devoluci__n"
               data-filter="tipo">
        <label class="form-check-label small"
               for="filter-tipo-devoluci__n">devolución</label>
    </div>
    </div>
</div>
                    </div>
                </div>
            </div>

            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#fs-scoped-0-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-scoped-0-cobro">
                    <span class="filter-group-icon">👤</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Deudor</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-scoped-0-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Deudor</label>
    <select name="filter[deudor_cliente_id]"
            class="form-select form-select-sm"
            data-filter="deudor_cliente_id">
        <option value="">Todos</option>
        @php($deudorOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($deudorOptions as $opt)
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
                     data-bs-target="#fs-scoped-1-cobro"
                     role="button"
                     aria-expanded="false"
                     aria-controls="fs-scoped-1-cobro">
                    <span class="filter-group-icon">👤</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">Filtrar por Acreedor</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse" id="fs-scoped-1-cobro">
                    <div class="row g-3 px-2 py-2">
                        <div class="mb-2 col-md-3">
    <label class="form-label small text-secondary mb-1">Acreedor</label>
    <select name="filter[acreedor_cliente_id]"
            class="form-select form-select-sm"
            data-filter="acreedor_cliente_id">
        <option value="">Todos</option>
        @php($acreedorOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre as display']))
        @foreach($acreedorOptions as $opt)
        <option value="{{ $opt->id }}">{{ $opt->display }}</option>
        @endforeach
    </select>
</div>
                    </div>
                </div>
            </div>
        <div class="row mt-3 pt-2 border-top">
            <div class="col-12 d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="btn-apply-filters-cobro">
                    <i class="bi bi-funnel"></i> Aplicar filtros
                </button>
                <a href="/cobro" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <small class="text-muted ms-auto" id="filter-total-cobro"></small>
            </div>
        </div>
    </div>
</div>