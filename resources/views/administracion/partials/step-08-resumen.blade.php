{{-- Step 8: Resumen --}}
<h4 class="mb-4">📋 Resumen de la Administración</h4>
<p class="text-muted">Revise la información antes de crear la administración.</p>

<dl class="row">
    <dt class="col-sm-4">Arrendador</dt>
    <dd class="col-sm-8" id="resumen-arrendador">
        <span x-text="document.getElementById('input-arrendador')?.value || '{{ old('arrendador_nombre', 'No ingresado') }}'">-</span>
    </dd>

    <dt class="col-sm-4">Arrendatario</dt>
    <dd class="col-sm-8" id="resumen-arrendatario">
        <span x-text="document.getElementById('input-arrendatario')?.value || '{{ old('arrendatario_nombre', 'No ingresado') }}'">-</span>
    </dd>

    <dt class="col-sm-4">Propiedad</dt>
    <dd class="col-sm-8" id="resumen-propiedad">
        <span x-text="document.getElementById('input-propiedad')?.value || '{{ old('propiedad_direccion', 'No ingresada') }}'">-</span>
    </dd>

    <dt class="col-sm-4">Renta</dt>
    <dd class="col-sm-8">
        $ <span x-text="document.querySelector('[name=renta]')?.value || '{{ old('renta', '-') }}'">-</span> CLP
    </dd>

    <dt class="col-sm-4">Administración</dt>
    <dd class="col-sm-8">
        <span x-text="administracion ? 'Sí' : 'No'">-</span>
    </dd>

    <template x-if="administracion">
        <div class="w-100">
            <dt class="col-sm-4">Comisión Mensual</dt>
            <dd class="col-sm-8">
                $ <span x-text="document.querySelector('[name=comision_mensual]')?.value || '0'">-</span> CLP
            </dd>
            <dt class="col-sm-4">Día de Pago</dt>
            <dd class="col-sm-8">
                Día <span x-text="document.querySelector('[name=dia_pago]')?.value || '-'">-</span>
            </dd>
            <dt class="col-sm-4">Comisión Inicial</dt>
            <dd class="col-sm-8">
                $ <span x-text="document.querySelector('[name=comision_inicial]')?.value || '0'">-</span> CLP
            </dd>
            <dt class="col-sm-4">Garantía</dt>
            <dd class="col-sm-8">
                $ <span x-text="document.querySelector('[name=garantia]')?.value || '0'">-</span> CLP
            </dd>
        </div>
    </template>

    <dt class="col-sm-4">Fecha de Inicio</dt>
    <dd class="col-sm-8" x-text="document.querySelector('[name=fecha_inicio]')?.value || '{{ old('fecha_inicio', '-') }}'">-</dd>

    <dt class="col-sm-4">Fecha de Término</dt>
    <dd class="col-sm-8" x-text="document.querySelector('[name=fecha_termino]')?.value || 'Sin fecha fija'">-</dd>
</dl>
