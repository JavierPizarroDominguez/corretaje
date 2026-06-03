@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Contrato #{{ $contrato->id }}</h2>
        <div>
            <a href="/contrato/{{ $contrato->id }}/edit" class="btn btn-secondary btn-sm">Editar</a>
            <a href="/contrato" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>
    </div>

    {{-- [GEN:START:component_table] --}}
    <table class="table table-bordered">
    {{-- [GEN:START:field_unidad] @gen:editable @gen:type:relation-fk @gen:related:Unidad --}}
    <tr>
        <td><b>Unidad:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-unidad">
            @if($contrato->Unidad_id)
                <a href="/unidad/{{ $contrato->Unidad_id }}">
                    {{ $contrato->unidad->nombre ?? $contrato->Unidad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Unidad</span>
            @endif
        </td>
        <td id="btn-contrato-{{ $contrato->id }}-unidad">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-unidad', 'btn-contrato-{{ $contrato->id }}-unidad', 'form-contrato-{{ $contrato->id }}-unidad', 'input-contrato-{{ $contrato->id }}-unidad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $contrato->Unidad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-unidad" colspan="2" style="display:none;">
            @if($unidadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/contrato/{{ $contrato->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-contrato-{{ $contrato->id }}-unidad"
                               name="nombre-unidad"
                               class="form-control form-control-sm"
                               value="{{ $contrato->unidad->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Unidad..."
                               onchange="if(this.value) { document.getElementById('hidden-contrato-{{ $contrato->id }}-unidad').value = ''; }">
                        <div id="lista-contrato-{{ $contrato->id }}-Unidad"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/contrato/{{ $contrato->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-contrato-{{ $contrato->id }}-unidad" name="Unidad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($unidadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $contrato->Unidad_id == $option->id ? 'selected' : '' }}>
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


    {{-- [GEN:START:field_administracion] @gen:editable @gen:type:boolean --}}
    <tr>
        <td><b>Administracion:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-administracion">
            {{ $contrato->administracion ? 'Sí' : 'No' }}
        </td>
        <td id="btn-contrato-{{ $contrato->id }}-administracion">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-administracion', 'btn-contrato-{{ $contrato->id }}-administracion', 'form-contrato-{{ $contrato->id }}-administracion', 'input-contrato-{{ $contrato->id }}-administracion')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-administracion" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <select id="input-contrato-{{ $contrato->id }}-administracion" name="administracion" class="form-select form-select-sm">
                    <option value="1" {{ $contrato->administracion ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ !$contrato->administracion ? 'selected' : '' }}>No</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_administracion] --}}


    {{-- [GEN:START:field_comision_inicial] @gen:editable --}}
    <tr>
        <td><b>Comision Inicial:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-comision_inicial">{{ $contrato->comision_inicial }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-comision_inicial">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-comision_inicial', 'btn-contrato-{{ $contrato->id }}-comision_inicial', 'form-contrato-{{ $contrato->id }}-comision_inicial', 'input-contrato-{{ $contrato->id }}-comision_inicial')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-comision_inicial" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-comision_inicial" name="comision_inicial" type="number" value="{{ $contrato->comision_inicial }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_comision_inicial] --}}


    {{-- [GEN:START:field_garantia] @gen:editable --}}
    <tr>
        <td><b>Garantia:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-garantia">{{ $contrato->garantia }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-garantia">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-garantia', 'btn-contrato-{{ $contrato->id }}-garantia', 'form-contrato-{{ $contrato->id }}-garantia', 'input-contrato-{{ $contrato->id }}-garantia')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-garantia" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-garantia" name="garantia" type="number" value="{{ $contrato->garantia }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_garantia] --}}


    {{-- [GEN:START:field_renta] @gen:editable --}}
    <tr>
        <td><b>Renta:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-renta">{{ $contrato->renta }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-renta">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-renta', 'btn-contrato-{{ $contrato->id }}-renta', 'form-contrato-{{ $contrato->id }}-renta', 'input-contrato-{{ $contrato->id }}-renta')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-renta" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-renta" name="renta" type="number" value="{{ $contrato->renta }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_renta] --}}


    {{-- [GEN:START:field_dia_pago] @gen:editable --}}
    <tr>
        <td><b>Dia Pago:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-dia_pago">{{ $contrato->dia_pago }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-dia_pago">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-dia_pago', 'btn-contrato-{{ $contrato->id }}-dia_pago', 'form-contrato-{{ $contrato->id }}-dia_pago', 'input-contrato-{{ $contrato->id }}-dia_pago')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-dia_pago" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-dia_pago" name="dia_pago" type="number" value="{{ $contrato->dia_pago }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_dia_pago] --}}


    {{-- [GEN:START:field_comision_mensual] @gen:editable --}}
    <tr>
        <td><b>Comision Mensual:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-comision_mensual">{{ $contrato->comision_mensual }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-comision_mensual">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-comision_mensual', 'btn-contrato-{{ $contrato->id }}-comision_mensual', 'form-contrato-{{ $contrato->id }}-comision_mensual', 'input-contrato-{{ $contrato->id }}-comision_mensual')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-comision_mensual" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-comision_mensual" name="comision_mensual" type="number" value="{{ $contrato->comision_mensual }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_comision_mensual] --}}


    {{-- [GEN:START:field_fecha_firma] @gen:editable --}}
    <tr>
        <td><b>Fecha Firma:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-fecha_firma">{{ $contrato->fecha_firma }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-fecha_firma">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-fecha_firma', 'btn-contrato-{{ $contrato->id }}-fecha_firma', 'form-contrato-{{ $contrato->id }}-fecha_firma', 'input-contrato-{{ $contrato->id }}-fecha_firma')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-fecha_firma" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-fecha_firma" name="fecha_firma" type="datetime-local" value="{{ $contrato->fecha_firma }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha_firma] --}}


    {{-- [GEN:START:field_fecha_inicio] @gen:editable --}}
    <tr>
        <td><b>Fecha Inicio:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-fecha_inicio">{{ $contrato->fecha_inicio }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-fecha_inicio">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-fecha_inicio', 'btn-contrato-{{ $contrato->id }}-fecha_inicio', 'form-contrato-{{ $contrato->id }}-fecha_inicio', 'input-contrato-{{ $contrato->id }}-fecha_inicio')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-fecha_inicio" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-fecha_inicio" name="fecha_inicio" type="datetime-local" value="{{ $contrato->fecha_inicio }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha_inicio] --}}


    {{-- [GEN:START:field_fecha_termino] @gen:editable --}}
    <tr>
        <td><b>Fecha Termino:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-fecha_termino">{{ $contrato->fecha_termino }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-fecha_termino">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-fecha_termino', 'btn-contrato-{{ $contrato->id }}-fecha_termino', 'form-contrato-{{ $contrato->id }}-fecha_termino', 'input-contrato-{{ $contrato->id }}-fecha_termino')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-fecha_termino" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-fecha_termino" name="fecha_termino" type="datetime-local" value="{{ $contrato->fecha_termino }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha_termino] --}}


    {{-- [GEN:START:field_url_pdf] @gen:editable --}}
    <tr>
        <td><b>Url Pdf:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-url_pdf">{{ $contrato->url_pdf }}</td>
        <td id="btn-contrato-{{ $contrato->id }}-url_pdf">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-url_pdf', 'btn-contrato-{{ $contrato->id }}-url_pdf', 'form-contrato-{{ $contrato->id }}-url_pdf', 'input-contrato-{{ $contrato->id }}-url_pdf')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-url_pdf" colspan="2" style="display:none;">
            <form method="POST" action="/contrato/{{ $contrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-contrato-{{ $contrato->id }}-url_pdf" name="url_pdf" type="text" value="{{ $contrato->url_pdf }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_url_pdf] --}}


    {{-- [GEN:START:field_ciudad] @gen:editable @gen:type:relation-fk @gen:related:Ciudad --}}
    <tr>
        <td><b>Ciudad:</b></td>
        <td id="td-contrato-{{ $contrato->id }}-ciudad">
            @if($contrato->Ciudad_id)
                <a href="/ciudad/{{ $contrato->Ciudad_id }}">
                    {{ $contrato->ciudad->nombre ?? $contrato->Ciudad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Ciudad</span>
            @endif
        </td>
        <td id="btn-contrato-{{ $contrato->id }}-ciudad">
            <button onclick="editarCampo('td-contrato-{{ $contrato->id }}-ciudad', 'btn-contrato-{{ $contrato->id }}-ciudad', 'form-contrato-{{ $contrato->id }}-ciudad', 'input-contrato-{{ $contrato->id }}-ciudad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $contrato->Ciudad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-contrato-{{ $contrato->id }}-ciudad" colspan="2" style="display:none;">
            @if($ciudadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/contrato/{{ $contrato->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-contrato-{{ $contrato->id }}-ciudad"
                               name="nombre-ciudad"
                               class="form-control form-control-sm"
                               value="{{ $contrato->ciudad->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Ciudad..."
                               onchange="if(this.value) { document.getElementById('hidden-contrato-{{ $contrato->id }}-ciudad').value = ''; }">
                        <div id="lista-contrato-{{ $contrato->id }}-Ciudad"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/contrato/{{ $contrato->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-contrato-{{ $contrato->id }}-ciudad" name="Ciudad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($ciudadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $contrato->Ciudad_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_ciudad] --}}

    </table>

    @push('scripts')
    <script>
    buscador({
        input: '#input-contrato-{{ $contrato->id }}-unidad',
        list:  '#lista-contrato-{{ $contrato->id }}-Unidad',
        tipo:  'unidad',
        onSelect: function(item) {
            document.getElementById('input-contrato-{{ $contrato->id }}-unidad').value = item.texto;
            document.getElementById('input-contrato-{{ $contrato->id }}-unidad').closest('form').submit();
        }
    });

    buscador({
        input: '#input-contrato-{{ $contrato->id }}-ciudad',
        list:  '#lista-contrato-{{ $contrato->id }}-Ciudad',
        tipo:  'ciudad',
        onSelect: function(item) {
            document.getElementById('input-contrato-{{ $contrato->id }}-ciudad').value = item.texto;
            document.getElementById('input-contrato-{{ $contrato->id }}-ciudad').closest('form').submit();
        }
    });
    </script>
    @endpush
    {{-- [GEN:END:component_table] --}}
</div>
@endsection
