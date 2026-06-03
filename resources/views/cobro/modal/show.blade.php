{{--
    modal/show.blade.php — Contenido para el modal de Cobro

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Cobro', vista: 'vista-cobro-{{ $cobro->id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-cobro-{{ $cobro->id }}">
                @include('cobro.modal.show', ['cobro' => $cobro])
            </div>
        </div>
--}}

<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_fecha_cobro] @gen:editable --}}
    <tr>
        <td><b>Fecha Cobro:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-fecha_cobro">{{ $cobro->fecha_cobro->translatedFormat('j \de F \d\e\l Y') }}</td>
        <td id="btn-cobro-{{ $cobro->id }}-fecha_cobro">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-fecha_cobro', 'btn-cobro-{{ $cobro->id }}-fecha_cobro', 'form-cobro-{{ $cobro->id }}-fecha_cobro', 'input-cobro-{{ $cobro->id }}-fecha_cobro')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-fecha_cobro" colspan="2" style="display:none;">
            <form method="POST" action="/cobro/{{ $cobro->id }}">
                @csrf
                @method('PUT')
                <input id="input-cobro-{{ $cobro->id }}-fecha_cobro" name="fecha_cobro" type="datetime-local" value="{{ $cobro->fecha_cobro }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha_cobro] --}}

    {{-- [GEN:START:field_monto] @gen:editable --}}
    <tr>
        <td><b>Monto:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-monto">{{ $cobro->monto ? '$' . number_format($cobro->monto, 0, ',', '.') : 'Sin monto' }}</td>
        <td id="btn-cobro-{{ $cobro->id }}-monto">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-monto', 'btn-cobro-{{ $cobro->id }}-monto', 'form-cobro-{{ $cobro->id }}-monto', 'input-cobro-{{ $cobro->id }}-monto')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-monto" colspan="2" style="display:none;">
            <form method="POST" action="/cobro/{{ $cobro->id }}">
                @csrf
                @method('PUT')
                <input id="input-cobro-{{ $cobro->id }}-monto" name="monto" type="number" value="{{ $cobro->monto }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_monto] --}}

    {{-- [GEN:START:field_deudor] @gen:editable @gen:type:relation-fk @gen:related:Cliente --}}
    <tr>
        <td><b>Deudor:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-deudor">
            @if($cobro->deudor)
                <a href="/cliente/ficha/{{ $cobro->deudor?->cliente?->id }}">
                    {{ $cobro->deudor?->nombre ?? 'Sin nombre' }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Deudor</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-deudor">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-deudor', 'btn-cobro-{{ $cobro->id }}-deudor', 'form-cobro-{{ $cobro->id }}-deudor', 'input-cobro-{{ $cobro->id }}-deudor')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->deudor ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-deudor" colspan="2" style="display:none;">
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="deudor_Cliente_id" id="hidden-cobro-{{ $cobro->id }}-deudor" value="">
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-deudor"
                               name="nombre-deudor"
                               class="form-control form-control-sm"
                               value="{{ $cobro->deudor?->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Deudor..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-deudor').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Deudor"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="deudor_Cliente_id" id="hidden-cobro-{{ $cobro->id }}-deudor" value="">
                    <select id="input-cobro-{{ $cobro->id }}-deudor" name="deudor_Cliente_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($clienteOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->deudor?->Cliente_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_deudor] --}}


    {{-- [GEN:START:field_acreedor] @gen:editable @gen:type:relation-fk @gen:related:Cliente --}}
    <tr>
        <td><b>Acreedor:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-acreedor">
            @if($cobro->acreedor)
                <a href="/cliente/ficha/{{ $cobro->acreedor?->cliente?->id }}">
                    {{ $cobro->acreedor?->nombre ?? 'Sin nombre' }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Acreedor</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-acreedor">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-acreedor', 'btn-cobro-{{ $cobro->id }}-acreedor', 'form-cobro-{{ $cobro->id }}-acreedor', 'input-cobro-{{ $cobro->id }}-acreedor')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->acreedor ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-acreedor" colspan="2" style="display:none;">
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="acreedor_Cliente_id" id="hidden-cobro-{{ $cobro->id }}-acreedor" value="">
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-acreedor"
                               name="nombre-acreedor"
                               class="form-control form-control-sm"
                               value="{{ $cobro->acreedor?->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Acreedor..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-acreedor').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Acreedor"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="acreedor_Cliente_id" id="hidden-cobro-{{ $cobro->id }}-acreedor" value="">
                    <select id="input-cobro-{{ $cobro->id }}-acreedor" name="acreedor_Cliente_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($clienteOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->acreedor?->Cliente_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_acreedor] --}}

</table>
<div class="d-flex gap-2">
    <a href="/cobro/{{ $cobro->id }}" id="btn-pagar-pendientes-cliente" class="btn btn-primary btn-sm">Registrar Pago</a>
    <a href="/cobro/{{ $cobro->id }}" class="btn btn-secondary btn-sm">Ver detalle</a>
</div>

@push('scripts')
<script>
    buscador({
        input: '#input-cobro-{{ $cobro->id }}-contrato',
        list:  '#lista-cobro-{{ $cobro->id }}-Contrato',
        tipo:  'contrato',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-contrato').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-contrato').closest('form').submit();
        }
    });

    buscador({
        input: '#input-cobro-{{ $cobro->id }}-servicio',
        list:  '#lista-cobro-{{ $cobro->id }}-Servicio',
        tipo:  'servicio',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-servicio').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-servicio').closest('form').submit();
        }
    });

    buscador({
        input: '#input-cobro-{{ $cobro->id }}-propiedad',
        list:  '#lista-cobro-{{ $cobro->id }}-Propiedad',
        tipo:  'propiedad',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-propiedad').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-propiedad').closest('form').submit();
        }
    });

    buscador({
        input: '#input-cobro-{{ $cobro->id }}-unidad',
        list:  '#lista-cobro-{{ $cobro->id }}-Unidad',
        tipo:  'unidad',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-unidad').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-unidad').closest('form').submit();
        }
    });

    buscador({
        input: '#input-cobro-{{ $cobro->id }}-deudor',
        list:  '#lista-cobro-{{ $cobro->id }}-Deudor',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-deudor').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-deudor').closest('form').submit();
        }
    });

    buscador({
        input: '#input-cobro-{{ $cobro->id }}-acreedor',
        list:  '#lista-cobro-{{ $cobro->id }}-Acreedor',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-cobro-{{ $cobro->id }}-acreedor').value = item.texto;
            document.getElementById('input-cobro-{{ $cobro->id }}-acreedor').closest('form').submit();
        }
    });
</script>
@endpush
