{{--
    modal/show.blade.php — Contenido para el modal de Servicio

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Servicio', vista: 'vista-servicio-{{ $servicio->id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-servicio-{{ $servicio->id }}">
                @include('servicio.modal.show', ['servicio' => $servicio])
            </div>
        </div>
--}}

<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_tipo] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Tipo:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-tipo">{{ $servicio->tipo }}</td>
        <td id="btn-servicio-{{ $servicio->id }}-tipo">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-tipo', 'btn-servicio-{{ $servicio->id }}-tipo', 'form-servicio-{{ $servicio->id }}-tipo', 'input-servicio-{{ $servicio->id }}-tipo')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-tipo" colspan="2" style="display:none;">
            <form method="POST" action="/servicio/{{ $servicio->id }}">
                @csrf
                @method('PUT')
                <select id="input-servicio-{{ $servicio->id }}-tipo" name="tipo" class="form-select form-select-sm">
                    <option value="Luz" {{ $servicio->tipo === 'Luz' ? 'selected' : '' }}>Luz</option>
                    <option value="Agua" {{ $servicio->tipo === 'Agua' ? 'selected' : '' }}>Agua</option>
                    <option value="Gas" {{ $servicio->tipo === 'Gas' ? 'selected' : '' }}>Gas</option>
                    <option value="Gastos Comunes" {{ $servicio->tipo === 'Gastos Comunes' ? 'selected' : '' }}>Gastos Comunes</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_tipo] --}}


    {{-- [GEN:START:field_dia_pago] @gen:editable --}}
    <tr>
        <td><b>Dia Pago:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-dia_pago">{{ $servicio->dia_pago }}</td>
        <td id="btn-servicio-{{ $servicio->id }}-dia_pago">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-dia_pago', 'btn-servicio-{{ $servicio->id }}-dia_pago', 'form-servicio-{{ $servicio->id }}-dia_pago', 'input-servicio-{{ $servicio->id }}-dia_pago')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-dia_pago" colspan="2" style="display:none;">
            <form method="POST" action="/servicio/{{ $servicio->id }}">
                @csrf
                @method('PUT')
                <input id="input-servicio-{{ $servicio->id }}-dia_pago" name="dia_pago" type="number" value="{{ $servicio->dia_pago }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_dia_pago] --}}


    {{-- [GEN:START:field_propiedad] @gen:editable @gen:type:relation-fk @gen:related:Propiedad --}}
    <tr>
        <td><b>Propiedad:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-propiedad">
            @if($servicio->Propiedad_id)
                <a href="/propiedad/{{ $servicio->Propiedad_id }}">
                    {{ $servicio->propiedad->direccion ?? $servicio->Propiedad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Propiedad</span>
            @endif
        </td>
        <td id="btn-servicio-{{ $servicio->id }}-propiedad">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-propiedad', 'btn-servicio-{{ $servicio->id }}-propiedad', 'form-servicio-{{ $servicio->id }}-propiedad', 'input-servicio-{{ $servicio->id }}-propiedad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $servicio->Propiedad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-propiedad" colspan="2" style="display:none;">
            @if($propiedadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/servicio/{{ $servicio->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-servicio-{{ $servicio->id }}-propiedad"
                               name="direccion-propiedad"
                               class="form-control form-control-sm"
                               value="{{ $servicio->propiedad->direccion ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Propiedad..."
                               onchange="if(this.value) { document.getElementById('hidden-servicio-{{ $servicio->id }}-propiedad').value = ''; }">
                        <div id="lista-servicio-{{ $servicio->id }}-Propiedad"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/servicio/{{ $servicio->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-servicio-{{ $servicio->id }}-propiedad" name="Propiedad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($propiedadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $servicio->Propiedad_id == $option->id ? 'selected' : '' }}>
                                {{ $option->direccion }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_propiedad] --}}


    {{-- [GEN:START:field_estado] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Estado:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-estado">{{ $servicio->estado }}</td>
        <td id="btn-servicio-{{ $servicio->id }}-estado">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-estado', 'btn-servicio-{{ $servicio->id }}-estado', 'form-servicio-{{ $servicio->id }}-estado', 'input-servicio-{{ $servicio->id }}-estado')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-estado" colspan="2" style="display:none;">
            <form method="POST" action="/servicio/{{ $servicio->id }}">
                @csrf
                @method('PUT')
                <select id="input-servicio-{{ $servicio->id }}-estado" name="estado" class="form-select form-select-sm">
                    <option value="Activo" {{ $servicio->estado === 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Inactivo" {{ $servicio->estado === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_estado] --}}


    {{-- [GEN:START:field_numero_cliente] @gen:editable --}}
    <tr>
        <td><b>Numero Cliente:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-numero_cliente">{{ $servicio->numero_cliente }}</td>
        <td id="btn-servicio-{{ $servicio->id }}-numero_cliente">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-numero_cliente', 'btn-servicio-{{ $servicio->id }}-numero_cliente', 'form-servicio-{{ $servicio->id }}-numero_cliente', 'input-servicio-{{ $servicio->id }}-numero_cliente')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-numero_cliente" colspan="2" style="display:none;">
            <form method="POST" action="/servicio/{{ $servicio->id }}">
                @csrf
                @method('PUT')
                <input id="input-servicio-{{ $servicio->id }}-numero_cliente" name="numero_cliente" type="text" value="{{ $servicio->numero_cliente }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_numero_cliente] --}}


    {{-- [GEN:START:field_empresa] @gen:editable @gen:type:relation-fk @gen:related:Empresa --}}
    <tr>
        <td><b>Empresa:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-empresa">
            @if($servicio->Empresa_id)
                <a href="/empresa/{{ $servicio->Empresa_id }}">
                    {{ $servicio->empresa->nombre ?? $servicio->Empresa_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Empresa</span>
            @endif
        </td>
        <td id="btn-servicio-{{ $servicio->id }}-empresa">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-empresa', 'btn-servicio-{{ $servicio->id }}-empresa', 'form-servicio-{{ $servicio->id }}-empresa', 'input-servicio-{{ $servicio->id }}-empresa')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $servicio->Empresa_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-empresa" colspan="2" style="display:none;">
            @if($empresaCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/servicio/{{ $servicio->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-servicio-{{ $servicio->id }}-empresa"
                               name="nombre-empresa"
                               class="form-control form-control-sm"
                               value="{{ $servicio->empresa->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Empresa..."
                               onchange="if(this.value) { document.getElementById('hidden-servicio-{{ $servicio->id }}-empresa').value = ''; }">
                        <div id="lista-servicio-{{ $servicio->id }}-Empresa"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/servicio/{{ $servicio->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-servicio-{{ $servicio->id }}-empresa" name="Empresa_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($empresaOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $servicio->Empresa_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_empresa] --}}


    {{-- [GEN:START:field_monto_fijo] @gen:editable --}}
    <tr>
        <td><b>Monto Fijo:</b></td>
        <td id="td-servicio-{{ $servicio->id }}-monto_fijo">{{ $servicio->monto_fijo }}</td>
        <td id="btn-servicio-{{ $servicio->id }}-monto_fijo">
            <button onclick="editarCampo('td-servicio-{{ $servicio->id }}-monto_fijo', 'btn-servicio-{{ $servicio->id }}-monto_fijo', 'form-servicio-{{ $servicio->id }}-monto_fijo', 'input-servicio-{{ $servicio->id }}-monto_fijo')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-servicio-{{ $servicio->id }}-monto_fijo" colspan="2" style="display:none;">
            <form method="POST" action="/servicio/{{ $servicio->id }}">
                @csrf
                @method('PUT')
                <input id="input-servicio-{{ $servicio->id }}-monto_fijo" name="monto_fijo" type="number" value="{{ $servicio->monto_fijo }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_monto_fijo] --}}

</table>
<div class="d-flex gap-2">
    <a href="/servicio/{{ $servicio->id }}"
       class="btn btn-primary btn-sm">Ver completo</a>
</div>

@push('scripts')
<script>
    buscador({
        input: '#input-servicio-{{ $servicio->id }}-propiedad',
        list:  '#lista-servicio-{{ $servicio->id }}-Propiedad',
        tipo:  'propiedad',
        onSelect: function(item) {
            document.getElementById('input-servicio-{{ $servicio->id }}-propiedad').value = item.texto;
            document.getElementById('input-servicio-{{ $servicio->id }}-propiedad').closest('form').submit();
        }
    });

    buscador({
        input: '#input-servicio-{{ $servicio->id }}-empresa',
        list:  '#lista-servicio-{{ $servicio->id }}-Empresa',
        tipo:  'empresa',
        onSelect: function(item) {
            document.getElementById('input-servicio-{{ $servicio->id }}-empresa').value = item.texto;
            document.getElementById('input-servicio-{{ $servicio->id }}-empresa').closest('form').submit();
        }
    });
</script>
@endpush
