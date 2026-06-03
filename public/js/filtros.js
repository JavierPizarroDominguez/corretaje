/**
 * filtros.js — Auto-filter con AJAX para paneles de filtro colapsables
 *
 * Uso:
 *   initFilters({ baseUrl: '/cobro', tableSelector: '#cobros-table',
 *                  filterPanel: '#filter-panel-cobro' });
 *
 * Escucha cambios en inputs con data-filter, hace debounce y actualiza
 * la tabla vía AJAX. Soporta date presets, checkboxes de enum, etc.
 */
function initFilters(config) {

    var baseUrl      = config.baseUrl;
    var tableBody    = document.querySelector(config.tableSelector + ' tbody');
    var paginationEl = document.querySelector(config.tableSelector + ' ~ .pagination-area');
    var filterPanel  = document.querySelector(config.filterPanel);
    var totalEl      = document.getElementById('filter-total-' + config.filterPanel.replace('#filter-panel-', ''));
    var toggleBtn    = document.querySelector('[data-toggle="filter"][data-target="' + config.filterPanel + '"]');

    if (!tableBody) return;

    var debounceTimer = null;
    var currentAbort  = null;

    function collectFilters() {
        var filters = {};
        var inputs = filterPanel.querySelectorAll('[data-filter]');
        inputs.forEach(function(input) {
            var name = input.getAttribute('data-filter');
            if (input.type === 'checkbox') {
                if (input.checked) {
                    if (!filters[name]) filters[name] = [];
                    filters[name].push(input.value);
                }
            } else if (input.value !== '') {
                filters[name] = input.value;
            }
        });
        return filters;
    }

    function applyFilters() {
        if (currentAbort) currentAbort.abort();

        var controller = new AbortController();
        currentAbort = controller;

        var filters = collectFilters();
        var params = new URLSearchParams();
        params.append('ajax', '1');

        Object.keys(filters).forEach(function(key) {
            var val = filters[key];
            if (Array.isArray(val)) {
                val.forEach(function(v) {
                    params.append('filter[' + key + '][]', v);
                });
            } else {
                params.append('filter[' + key + ']', val);
            }
        });

        var url = baseUrl + '-filtrar?' + params.toString();

        if (typeof window.showElLoading === 'function') {
            window.showElLoading(tableBody, 99);
        } else {
            tableBody.innerHTML = '<tr><td colspan="99" class="text-center py-4">' +
                '<div class="spinner-border spinner-border-sm text-secondary" role="status"></div>' +
                ' Buscando...</td></tr>';
        }

        fetch(url, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(r) {
                if (!r.ok) throw new Error('Error ' + r.status);
                return r.json();
            })
            .then(function(data) {
                if (typeof window.hideElLoading === 'function') {
                    window.hideElLoading(tableBody);
                }
                tableBody.innerHTML = data.rows;
                if (paginationEl) paginationEl.innerHTML = data.pagination;
                if (totalEl) totalEl.textContent = data.total + ' resultados';
            })
            .catch(function(e) {
                if (e.name !== 'AbortError') {
                    console.error('Filter error:', e);
                    if (typeof window.hideElLoading === 'function') {
                        window.hideElLoading(tableBody);
                    }
                    tableBody.innerHTML = '<tr><td colspan="99" class="text-center py-4 text-danger">' +
                        '<i class="bi bi-exclamation-triangle"></i> Error al filtrar</td></tr>';
                }
            });
    }

    function debouncedApply() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, 300);
    }

    // ── Listeners en inputs del panel ──────────────────────────
    if (filterPanel) {
        filterPanel.querySelectorAll('[data-filter]').forEach(function(input) {
            if (input.type === 'checkbox') {
                input.addEventListener('change', debouncedApply);
            } else {
                input.addEventListener('input', debouncedApply);
                input.addEventListener('change', debouncedApply);
            }
        });

        // Botón aplicar (por si quieren forzar)
        var applyBtn = filterPanel.querySelector('[id^="btn-apply-filters-"]');
        if (applyBtn) {
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                applyFilters();
            });
        }
    }

    // ── Date presets ─────────────────────────────────────────────
    document.querySelectorAll('.date-preset').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var field = btn.getAttribute('data-field');
            var days  = parseInt(btn.getAttribute('data-days'), 10);
            var panel = btn.closest('[id^="filter-panel-"]');
            if (!panel) return;

            var fromInput = panel.querySelector('[data-filter="' + field + '_from"]');
            var toInput   = panel.querySelector('[data-filter="' + field + '_to"]');
            if (!fromInput || !toInput) return;

            var today = new Date();
            var toStr = formatDate(today);

            if (days === 0) {
                fromInput.value = toStr;
                toInput.value   = toStr;
            } else {
                var from = new Date(today);
                from.setDate(from.getDate() - days);
                fromInput.value = formatDate(from);
                toInput.value   = toStr;
            }

            applyFilters();
        });
    });

    function formatDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }
}