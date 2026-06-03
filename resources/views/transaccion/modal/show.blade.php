{{--
    modal/show.blade.php — Contenido para el modal de Transaccion

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Transaccion', vista: 'vista-transaccion-{{ $transaccion->id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-transaccion-{{ $transaccion->id }}">
                @include('transaccion.modal.show', ['transaccion' => $transaccion])
            </div>
        </div>
--}}

<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_monto] @gen:editable --}}
    <tr>
        <td><b>Monto:</b></td>
        <td id="td-transaccion-monto">{{ $transaccion->monto }}</td>
        <td id="btn-transaccion-monto">
            <button onclick="editarCampo('td-transaccion-monto', 'btn-transaccion-monto', 'form-transaccion-monto', 'input-transaccion-monto')">Editar</button>
        </td>
        <td id="form-transaccion-monto" colspan="2" style="display:none;">
            <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                @csrf
                @method('PUT')
                <input id="input-transaccion-monto" name="monto" type="number" value="{{ $transaccion->monto }}">
                <input type="submit" value="Modificar">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_monto] --}}


    {{-- [GEN:START:field_fecha] @gen:editable --}}
    <tr>
        <td><b>Fecha:</b></td>
        <td id="td-transaccion-fecha">{{ $transaccion->fecha }}</td>
        <td id="btn-transaccion-fecha">
            <button onclick="editarCampo('td-transaccion-fecha', 'btn-transaccion-fecha', 'form-transaccion-fecha', 'input-transaccion-fecha')">Editar</button>
        </td>
        <td id="form-transaccion-fecha" colspan="2" style="display:none;">
            <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                @csrf
                @method('PUT')
                <input id="input-transaccion-fecha" name="fecha" type="datetime-local" value="{{ $transaccion->fecha }}">
                <input type="submit" value="Modificar">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha] --}}


    {{-- [GEN:START:field_destino_transaccion] @gen:editable @gen:type:relation-fk @gen:related:DestinoTransaccion --}}
    <tr>
        <td><b>Destino Transaccion:</b></td>
        <td id="td-destino_transaccion">
            @if($transaccion->Destino_Transaccion_id)
                <a href="/destino_transaccion/{{ $transaccion->Destino_Transaccion_id }}">
                    {{ $transaccion->destino_transaccion->id ?? $transaccion->Destino_Transaccion_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Destino Transaccion</span>
            @endif
        </td>
        <td id="btn-destino_transaccion">
            <button onclick="editarCampo('td-destino_transaccion', 'btn-destino_transaccion', 'form-destino_transaccion', 'input-destino_transaccion')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $transaccion->Destino_Transaccion_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-destino_transaccion" colspan="2" style="display:none;">
            @if($destinotransaccionCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                    @csrf
                    @method('PUT')
                    <div class="position-relative">
                        <input id="input-destino_transaccion"
                               name="id-destino_transaccion"
                               class="form-control form-control-sm"
                               value="{{ $transaccion->destino_transaccion->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Destino Transaccion...">
                        <div id="listaDestinoTransaccion"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select con opción para agregar nuevo --}}
                <div id="form-select-destino_transaccion">
                    <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                        @csrf
                        @method('PUT')
                        <select id="input-destino_transaccion" name="Destino_Transaccion_id" class="form-select form-select-sm"
                                onchange="if(this.value==='__nuevo__'){
                                    document.getElementById('form-select-destino_transaccion').style.display='none';
                                    document.getElementById('form-buscador-destino_transaccion').style.display='block';
                                }">
                            <option value="">— Seleccionar —</option>
                            @foreach($destinotransaccionOptions as $option)
                                <option value="{{ $option->id }}"
                                        {{ $transaccion->Destino_Transaccion_id == $option->id ? 'selected' : '' }}>
                                    {{ $option->id }}
                                </option>
                            @endforeach
                            <option value="__nuevo__">+ Agregar Destino Transaccion</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                </div>
                {{-- Buscador para agregar nuevo --}}
                <div id="form-buscador-destino_transaccion" style="display:none;" class="position-relative">
                    <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                        @csrf
                        @method('PUT')
                        <input id="input-buscador-destino_transaccion"
                               name="id-destino_transaccion"
                               class="form-control form-control-sm"
                               placeholder="Buscar Destino Transaccion..."
                               autocomplete="off">
                        <div id="listaBuscadorDestinoTransaccion"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-link"
                            onclick="document.getElementById('form-buscador-destino_transaccion').style.display='none';
                                     document.getElementById('form-select-destino_transaccion').style.display='block';">
                        Cancelar
                    </button>
                </div>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_destino_transaccion] --}}


    {{-- [GEN:START:field_origen_transaccion] @gen:editable @gen:type:relation-fk @gen:related:OrigenTransaccion --}}
    <tr>
        <td><b>Origen Transaccion:</b></td>
        <td id="td-origen_transaccion">
            @if($transaccion->Origen_Transaccion_id)
                <a href="/origen_transaccion/{{ $transaccion->Origen_Transaccion_id }}">
                    {{ $transaccion->origen_transaccion->id ?? $transaccion->Origen_Transaccion_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Origen Transaccion</span>
            @endif
        </td>
        <td id="btn-origen_transaccion">
            <button onclick="editarCampo('td-origen_transaccion', 'btn-origen_transaccion', 'form-origen_transaccion', 'input-origen_transaccion')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $transaccion->Origen_Transaccion_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-origen_transaccion" colspan="2" style="display:none;">
            @if($origentransaccionCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                    @csrf
                    @method('PUT')
                    <div class="position-relative">
                        <input id="input-origen_transaccion"
                               name="id-origen_transaccion"
                               class="form-control form-control-sm"
                               value="{{ $transaccion->origen_transaccion->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Origen Transaccion...">
                        <div id="listaOrigenTransaccion"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select con opción para agregar nuevo --}}
                <div id="form-select-origen_transaccion">
                    <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                        @csrf
                        @method('PUT')
                        <select id="input-origen_transaccion" name="Origen_Transaccion_id" class="form-select form-select-sm"
                                onchange="if(this.value==='__nuevo__'){
                                    document.getElementById('form-select-origen_transaccion').style.display='none';
                                    document.getElementById('form-buscador-origen_transaccion').style.display='block';
                                }">
                            <option value="">— Seleccionar —</option>
                            @foreach($origentransaccionOptions as $option)
                                <option value="{{ $option->id }}"
                                        {{ $transaccion->Origen_Transaccion_id == $option->id ? 'selected' : '' }}>
                                    {{ $option->id }}
                                </option>
                            @endforeach
                            <option value="__nuevo__">+ Agregar Origen Transaccion</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                </div>
                {{-- Buscador para agregar nuevo --}}
                <div id="form-buscador-origen_transaccion" style="display:none;" class="position-relative">
                    <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                        @csrf
                        @method('PUT')
                        <input id="input-buscador-origen_transaccion"
                               name="id-origen_transaccion"
                               class="form-control form-control-sm"
                               placeholder="Buscar Origen Transaccion..."
                               autocomplete="off">
                        <div id="listaBuscadorOrigenTransaccion"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-link"
                            onclick="document.getElementById('form-buscador-origen_transaccion').style.display='none';
                                     document.getElementById('form-select-origen_transaccion').style.display='block';">
                        Cancelar
                    </button>
                </div>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_origen_transaccion] --}}


    {{-- [GEN:START:field_url_comprobante] @gen:editable --}}
    <tr>
        <td><b>Url Comprobante:</b></td>
        <td id="td-transaccion-url_comprobante">{{ $transaccion->url_comprobante }}</td>
        <td id="btn-transaccion-url_comprobante">
            <button onclick="editarCampo('td-transaccion-url_comprobante', 'btn-transaccion-url_comprobante', 'form-transaccion-url_comprobante', 'input-transaccion-url_comprobante')">Editar</button>
        </td>
        <td id="form-transaccion-url_comprobante" colspan="2" style="display:none;">
            <form method="POST" action="/transaccion/{{ $transaccion->id }}">
                @csrf
                @method('PUT')
                <input id="input-transaccion-url_comprobante" name="url_comprobante" type="text" value="{{ $transaccion->url_comprobante }}">
                <input type="submit" value="Modificar">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_url_comprobante] --}}

</table>
<div class="d-flex gap-2">
    <a href="/transaccion/{{ $transaccion->id }}"
       class="btn btn-primary btn-sm">Ver completo</a>
</div>
