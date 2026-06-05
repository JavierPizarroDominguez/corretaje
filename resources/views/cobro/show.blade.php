@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Cobro #{{ $cobro->id }}</h2>
        <div>
            <a href="/cobro/{{ $cobro->id }}/edit" class="btn btn-secondary btn-sm">Editar</a>
            <a href="#" id="back-btn" class="btn btn-outline-secondary btn-sm">Volver</a>
            <script>
            (function() {
                var params = new URLSearchParams(window.location.search);
                var from = params.get('from');
                var backBtn = document.getElementById('back-btn');
                if (from && from.charAt(0) === '/' && from.indexOf('//') !== 0 && from.toLowerCase().indexOf('javascript:') !== 0) {
                    backBtn.href = from;
                } else {
                    backBtn.href = '/cobro';
                }
            })();
            </script>
        </div>
    </div>

    {{-- [GEN:START:component_table] --}}
    <table class="table table-bordered">
    {{-- [GEN:START:field_fecha_cobro] @gen:editable --}}
    <tr>
        <td><b>Fecha Cobro:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-fecha_cobro">{{ $cobro->fecha_cobro }}</td>
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


    {{-- [GEN:START:field_estado] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Estado:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-estado">{{ $cobro->estado }}</td>
        <td id="btn-cobro-{{ $cobro->id }}-estado">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-estado', 'btn-cobro-{{ $cobro->id }}-estado', 'form-cobro-{{ $cobro->id }}-estado', 'input-cobro-{{ $cobro->id }}-estado')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-estado" colspan="2" style="display:none;">
            <form method="POST" action="/cobro/{{ $cobro->id }}">
                @csrf
                @method('PUT')
                <select id="input-cobro-{{ $cobro->id }}-estado" name="estado" class="form-select form-select-sm">
                    <option value="Pagado" {{ $cobro->estado === 'Pagado' ? 'selected' : '' }}>Pagado</option>
                    <option value="Incompleto" {{ $cobro->estado === 'Incompleto' ? 'selected' : '' }}>Incompleto</option>
                    <option value="Pendiente" {{ $cobro->estado === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="Vencido" {{ $cobro->estado === 'Vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="Anulado" {{ $cobro->estado === 'Anulado' ? 'selected' : '' }}>Anulado</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_estado] --}}


    {{-- [GEN:START:field_tipo] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Tipo:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-tipo">{{ $cobro->tipo }}</td>
        <td id="btn-cobro-{{ $cobro->id }}-tipo">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-tipo', 'btn-cobro-{{ $cobro->id }}-tipo', 'form-cobro-{{ $cobro->id }}-tipo', 'input-cobro-{{ $cobro->id }}-tipo')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-tipo" colspan="2" style="display:none;">
            <form method="POST" action="/cobro/{{ $cobro->id }}">
                @csrf
                @method('PUT')
                <select id="input-cobro-{{ $cobro->id }}-tipo" name="tipo" class="form-select form-select-sm">
                    <option value="Ingreso Renta Arrendatario" {{ $cobro->tipo === 'Ingreso Renta Arrendatario' ? 'selected' : '' }}>Ingreso Renta Arrendatario</option>
                    <option value="Egreso Renta Arrendador" {{ $cobro->tipo === 'Egreso Renta Arrendador' ? 'selected' : '' }}>Egreso Renta Arrendador</option>
                    <option value="Comision inicial arrendador" {{ $cobro->tipo === 'Comision inicial arrendador' ? 'selected' : '' }}>Comision inicial arrendador</option>
                    <option value="Comision inicial arrendatario" {{ $cobro->tipo === 'Comision inicial arrendatario' ? 'selected' : '' }}>Comision inicial arrendatario</option>
                    <option value="Comision Mensual" {{ $cobro->tipo === 'Comision Mensual' ? 'selected' : '' }}>Comision Mensual</option>
                    <option value="Ingreso Garantía Arrendatario" {{ $cobro->tipo === 'Ingreso Garantía Arrendatario' ? 'selected' : '' }}>Ingreso Garantía Arrendatario</option>
                    <option value="Egreso Garantía Arrendador" {{ $cobro->tipo === 'Egreso Garantía Arrendador' ? 'selected' : '' }}>Egreso Garantía Arrendador</option>
                    <option value="Devolución Garantía Arrendatario" {{ $cobro->tipo === 'Devolución Garantía Arrendatario' ? 'selected' : '' }}>Devolución Garantía Arrendatario</option>
                    <option value="Aseo Final" {{ $cobro->tipo === 'Aseo Final' ? 'selected' : '' }}>Aseo Final</option>
                    <option value="Luz" {{ $cobro->tipo === 'Luz' ? 'selected' : '' }}>Luz</option>
                    <option value="Agua" {{ $cobro->tipo === 'Agua' ? 'selected' : '' }}>Agua</option>
                    <option value="Gas" {{ $cobro->tipo === 'Gas' ? 'selected' : '' }}>Gas</option>
                    <option value="Gastos comunes" {{ $cobro->tipo === 'Gastos comunes' ? 'selected' : '' }}>Gastos comunes</option>
                    <option value="Reparación" {{ $cobro->tipo === 'Reparación' ? 'selected' : '' }}>Reparación</option>
                    <option value="Extra" {{ $cobro->tipo === 'Extra' ? 'selected' : '' }}>Extra</option>
                    <option value="Devolución" {{ $cobro->tipo === 'Devolución' ? 'selected' : '' }}>Devolución</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_tipo] --}}


    {{-- [GEN:START:field_monto] @gen:editable --}}
    <tr>
        <td><b>Monto:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-monto">{{ $cobro->monto }}</td>
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


    {{-- [GEN:START:field_detalle] @gen:editable --}}
    <tr>
        <td><b>Detalle:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-detalle">{{ $cobro->detalle }}</td>
        <td id="btn-cobro-{{ $cobro->id }}-detalle">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-detalle', 'btn-cobro-{{ $cobro->id }}-detalle', 'form-cobro-{{ $cobro->id }}-detalle', 'input-cobro-{{ $cobro->id }}-detalle')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-detalle" colspan="2" style="display:none;">
            <form method="POST" action="/cobro/{{ $cobro->id }}">
                @csrf
                @method('PUT')
                <input id="input-cobro-{{ $cobro->id }}-detalle" name="detalle" type="text" value="{{ $cobro->detalle }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_detalle] --}}


    {{-- [GEN:START:field_contrato] @gen:editable @gen:type:relation-fk @gen:related:Contrato --}}
    <tr>
        <td><b>Contrato:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-contrato">
            @if($cobro->Contrato_id)
                <a href="/contrato/{{ $cobro->Contrato_id }}">
                    {{ $cobro->contrato->id ?? $cobro->Contrato_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Contrato</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-contrato">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-contrato', 'btn-cobro-{{ $cobro->id }}-contrato', 'form-cobro-{{ $cobro->id }}-contrato', 'input-cobro-{{ $cobro->id }}-contrato')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->Contrato_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-contrato" colspan="2" style="display:none;">
            @if($contratoCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-contrato"
                               name="id-contrato"
                               class="form-control form-control-sm"
                               value="{{ $cobro->contrato->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Contrato..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-contrato').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Contrato"
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
                    
                    <select id="input-cobro-{{ $cobro->id }}-contrato" name="Contrato_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($contratoOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->Contrato_id == $option->id ? 'selected' : '' }}>
                                {{ $option->id }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_contrato] --}}


    {{-- [GEN:START:field_servicio] @gen:editable @gen:type:relation-fk @gen:related:Servicio --}}
    <tr>
        <td><b>Servicio:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-servicio">
            @if($cobro->Servicio_id)
                <a href="/servicio/{{ $cobro->Servicio_id }}">
                    {{ $cobro->servicio->id ?? $cobro->Servicio_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Servicio</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-servicio">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-servicio', 'btn-cobro-{{ $cobro->id }}-servicio', 'form-cobro-{{ $cobro->id }}-servicio', 'input-cobro-{{ $cobro->id }}-servicio')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->Servicio_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-servicio" colspan="2" style="display:none;">
            @if($servicioCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-servicio"
                               name="id-servicio"
                               class="form-control form-control-sm"
                               value="{{ $cobro->servicio->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Servicio..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-servicio').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Servicio"
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
                    
                    <select id="input-cobro-{{ $cobro->id }}-servicio" name="Servicio_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($servicioOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->Servicio_id == $option->id ? 'selected' : '' }}>
                                {{ $option->id }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_servicio] --}}


    {{-- [GEN:START:field_propiedad] @gen:editable @gen:type:relation-fk @gen:related:Propiedad --}}
    <tr>
        <td><b>Propiedad:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-propiedad">
            @if($cobro->Propiedad_id)
                <a href="/propiedad/{{ $cobro->Propiedad_id }}">
                    {{ $cobro->propiedad->direccion ?? $cobro->Propiedad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Propiedad</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-propiedad">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-propiedad', 'btn-cobro-{{ $cobro->id }}-propiedad', 'form-cobro-{{ $cobro->id }}-propiedad', 'input-cobro-{{ $cobro->id }}-propiedad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->Propiedad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-propiedad" colspan="2" style="display:none;">
            @if($propiedadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-propiedad"
                               name="direccion-propiedad"
                               class="form-control form-control-sm"
                               value="{{ $cobro->propiedad->direccion ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Propiedad..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-propiedad').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Propiedad"
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
                    
                    <select id="input-cobro-{{ $cobro->id }}-propiedad" name="Propiedad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($propiedadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->Propiedad_id == $option->id ? 'selected' : '' }}>
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


    {{-- [GEN:START:field_unidad] @gen:editable @gen:type:relation-fk @gen:related:Unidad --}}
    <tr>
        <td><b>Unidad:</b></td>
        <td id="td-cobro-{{ $cobro->id }}-unidad">
            @if($cobro->Unidad_id)
                <a href="/unidad/{{ $cobro->Unidad_id }}">
                    {{ $cobro->unidad->nombre ?? $cobro->Unidad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Unidad</span>
            @endif
        </td>
        <td id="btn-cobro-{{ $cobro->id }}-unidad">
            <button onclick="editarCampo('td-cobro-{{ $cobro->id }}-unidad', 'btn-cobro-{{ $cobro->id }}-unidad', 'form-cobro-{{ $cobro->id }}-unidad', 'input-cobro-{{ $cobro->id }}-unidad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cobro->Unidad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cobro-{{ $cobro->id }}-unidad" colspan="2" style="display:none;">
            @if($unidadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cobro/{{ $cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-cobro-{{ $cobro->id }}-unidad"
                               name="nombre-unidad"
                               class="form-control form-control-sm"
                               value="{{ $cobro->unidad->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Unidad..."
                               onchange="if(this.value) { document.getElementById('hidden-cobro-{{ $cobro->id }}-unidad').value = ''; }">
                        <div id="lista-cobro-{{ $cobro->id }}-Unidad"
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
                    
                    <select id="input-cobro-{{ $cobro->id }}-unidad" name="Unidad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($unidadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cobro->Unidad_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_unidad] --}}


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
    {{-- [GEN:END:component_table] --}}
</div>
@endsection
