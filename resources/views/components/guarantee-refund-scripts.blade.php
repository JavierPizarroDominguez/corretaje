@once
<script>
    (function () {
        function parseRefundCLP(value) {
            if (window.stripCLP) return parseInt(window.stripCLP(value), 10) || 0;
            return parseInt(String(value || '').replace(/\D/g, ''), 10) || 0;
        }

        function formatRefundCLP(value) {
            if (window.formatCLP) return window.formatCLP(value);
            return '$' + (parseInt(value, 10) || 0).toLocaleString('es-CL');
        }

        function refundBase(cobro) {
            return parseInt(cobro.refundable_base_amount ?? cobro.garantia_base ?? cobro.monto ?? 0, 10) || 0;
        }

        function remainingTerm(cobro) {
            var days = parseInt(cobro.plazo_restante_dias ?? 0, 10) || 0;
            return Math.max(0, days);
        }

        function discountRow() {
            var row = document.createElement('tr');
            row.className = 'garantia-refund-discount-row';
            row.innerHTML = '<td><select class="form-select form-select-sm" data-refund-concept>'
                + '<option value="Aseo Final" selected>Aseo final</option>'
                + '<option value="Reparación">Reparación</option>'
                + '</select></td>'
                + '<td><input type="text" class="form-control form-control-sm" placeholder="Detalle" data-refund-detail></td>'
                + '<td><input type="text" class="form-control form-control-sm" value="$0" data-refund-amount></td>'
                + '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-refund-remove>Quitar</button></td>';

            return row;
        }

        function collectRefundDiscounts(modalEl) {
            return Array.from(modalEl.querySelectorAll('.garantia-refund-discount-row')).map(function (row) {
                return {
                    concepto: row.querySelector('[data-refund-concept]').value,
                    detalle: row.querySelector('[data-refund-detail]').value.trim(),
                    monto: parseRefundCLP(row.querySelector('[data-refund-amount]').value)
                };
            }).filter(function (discount) {
                return discount.monto > 0;
            });
        }

        function totalRefundDiscounts(modalEl) {
            return collectRefundDiscounts(modalEl).reduce(function (total, discount) {
                return total + discount.monto;
            }, 0);
        }

        function recalculateRefund(modalEl) {
            var cobro = JSON.parse(modalEl.dataset.cobro || '{}');
            var base = refundBase(cobro);
            var total = Math.max(0, base - totalRefundDiscounts(modalEl));
            var totalEl = modalEl.querySelector('[data-refund-total]');

            if (totalEl) totalEl.textContent = formatRefundCLP(total);
        }

        function showRefundError(modalEl, message) {
            var errorEl = modalEl.querySelector('[data-refund-error]');
            if (!errorEl) return;
            errorEl.textContent = message || '';
            errorEl.classList.toggle('d-none', !message);
        }

        function resolveRefundError(json) {
            if (!json) return 'No se pudo devolver la garantía.';
            if (json.error) return json.error;
            if (json.message) return json.message;
            if (json.errors) {
                var firstKey = Object.keys(json.errors)[0];
                if (firstKey && json.errors[firstKey] && json.errors[firstKey][0]) return json.errors[firstKey][0];
            }

            return 'No se pudo devolver la garantía.';
        }

        window.isGuaranteeRefundCobro = function (cobro) {
            return Boolean(cobro && (cobro.is_guarantee_refund || cobro.tipo === 'Devolución Garantía Arrendatario'));
        };

        window.openGuaranteeRefundModal = function (cobro) {
            var modalEl = document.getElementById('modalGarantiaRefund');
            if (!modalEl) return;

            modalEl.dataset.cobro = JSON.stringify(cobro || {});
            modalEl.querySelector('[data-refund-base]').textContent = formatRefundCLP(refundBase(cobro || {}));
            modalEl.querySelector('[data-refund-plazo]').textContent = remainingTerm(cobro || {}) + ' días';
            modalEl.querySelector('[data-refund-total]').textContent = formatRefundCLP(refundBase(cobro || {}));
            modalEl.querySelector('[data-refund-discounts]').innerHTML = '';
            showRefundError(modalEl, '');

            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        };

        async function finalizeGuaranteeRefund(modalEl, btn) {
            var cobro = JSON.parse(modalEl.dataset.cobro || '{}');
            var discounts = collectRefundDiscounts(modalEl);
            try {
                btn.disabled = true;
                if (typeof window.showElLoading === 'function') window.showElLoading(btn);
                showRefundError(modalEl, '');

                var res = await fetch('/api/cobros/' + cobro.id + '/devolver-garantia', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ descuentos: discounts })
                });
                var json = await res.json();

                if (!res.ok || json.error || json.errors) {
                    showRefundError(modalEl, resolveRefundError(json));
                    return;
                }

                if (typeof window.mostrarMensaje === 'function') {
                    window.mostrarMensaje('Éxito', 'La garantía se devolvió correctamente.', 'success');
                }
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                if (typeof window.afterGuaranteeRefundFinalized === 'function') {
                    window.afterGuaranteeRefundFinalized();
                }
            } catch (error) {
                showRefundError(modalEl, 'Error de conexión');
            } finally {
                btn.disabled = false;
                if (typeof window.hideElLoading === 'function') window.hideElLoading(btn);
            }
        }

        document.addEventListener('click', function (event) {
            var modalEl = document.getElementById('modalGarantiaRefund');
            if (!modalEl) return;

            if (event.target.matches('[data-refund-add]')) {
                modalEl.querySelector('[data-refund-discounts]').appendChild(discountRow());
                recalculateRefund(modalEl);
            }
            if (event.target.matches('[data-refund-remove]')) {
                event.target.closest('.garantia-refund-discount-row').remove();
                recalculateRefund(modalEl);
            }
            if (event.target.id === 'btn-devolver-garantia') {
                finalizeGuaranteeRefund(modalEl, event.target);
            }
        });

        document.addEventListener('input', function (event) {
            var modalEl = event.target.closest('#modalGarantiaRefund');
            if (!modalEl) return;
            if (event.target.matches('[data-refund-amount]') && window.handleCLPInput) {
                window.handleCLPInput(event.target);
            }
            recalculateRefund(modalEl);
        });
    })();
</script>
@endonce
