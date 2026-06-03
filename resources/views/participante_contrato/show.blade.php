@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>ParticipanteContrato #{{ $participanteContrato->id }}</h2>
        <div>
            <a href="/participante_contrato/{{ $participanteContrato->id }}/edit" class="btn btn-secondary btn-sm">Editar</a>
            <a href="/participante_contrato" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>
    </div>

    {{-- [GEN:START:component_table] --}}
    <table class="table table-bordered">
    {{-- [GEN:START:field_cliente] @gen:editable @gen:type:relation-fk @gen:related:Cliente --}}
    <tr>
        <td><b>Cliente:</b></td>
        <td id="td-participanteContrato-{{ $participanteContrato->id }}-cliente">
            @if($participanteContrato->Cliente_id)
                <a href="/cliente/{{ $participanteContrato->Cliente_id }}">
                    {{ $participanteContrato->cliente->id ?? $participanteContrato->Cliente_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Cliente</span>
            @endif
        </td>
        <td id="btn-participanteContrato-{{ $participanteContrato->id }}-cliente">
            <button onclick="editarCampo('td-participanteContrato-{{ $participanteContrato->id }}-cliente', 'btn-participanteContrato-{{ $participanteContrato->id }}-cliente', 'form-participanteContrato-{{ $participanteContrato->id }}-cliente', 'input-participanteContrato-{{ $participanteContrato->id }}-cliente')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $participanteContrato->Cliente_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-participanteContrato-{{ $participanteContrato->id }}-cliente" colspan="2" style="display:none;">
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                    @csrf
                    @method('PUT')
                    <div class="position-relative">
                        <input id="input-participanteContrato-{{ $participanteContrato->id }}-cliente"
                               name="id-cliente"
                               class="form-control form-control-sm"
                               value="{{ $participanteContrato->cliente->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Cliente...">
                        <div id="lista-participanteContrato-{{ $participanteContrato->id }}-Cliente"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select con opción para agregar nuevo --}}
                <div id="form-select-participanteContrato-{{ $participanteContrato->id }}-cliente">
                    <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                        @csrf
                        @method('PUT')
                        <select id="input-participanteContrato-{{ $participanteContrato->id }}-cliente" name="Cliente_id" class="form-select form-select-sm"
                                onchange="if(this.value==='__nuevo__'){
                                    document.getElementById('form-select-participanteContrato-{{ $participanteContrato->id }}-cliente').style.display='none';
                                    document.getElementById('form-buscador-participanteContrato-{{ $participanteContrato->id }}-cliente').style.display='block';
                                }">
                            <option value="">— Seleccionar —</option>
                            @foreach($clienteOptions as $option)
                                <option value="{{ $option->id }}"
                                        {{ $participanteContrato->Cliente_id == $option->id ? 'selected' : '' }}>
                                    {{ $option->id }}
                                </option>
                            @endforeach
                            <option value="__nuevo__">+ Agregar Cliente</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                </div>
                {{-- Buscador para agregar nuevo --}}
                <div id="form-buscador-participanteContrato-{{ $participanteContrato->id }}-cliente" style="display:none;" class="position-relative">
                    <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                        @csrf
                        @method('PUT')
                        <input id="input-buscador-participanteContrato-{{ $participanteContrato->id }}-cliente"
                               name="id-cliente"
                               class="form-control form-control-sm"
                               placeholder="Buscar Cliente..."
                               autocomplete="off">
                        <div id="listaBuscador-participanteContrato-{{ $participanteContrato->id }}-Cliente"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-link"
                            onclick="document.getElementById('form-buscador-participanteContrato-{{ $participanteContrato->id }}-cliente').style.display='none';
                                     document.getElementById('form-select-participanteContrato-{{ $participanteContrato->id }}-cliente').style.display='block';">
                        Cancelar
                    </button>
                </div>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_cliente] --}}


    {{-- [GEN:START:field_contrato] @gen:editable @gen:type:relation-fk @gen:related:Contrato --}}
    <tr>
        <td><b>Contrato:</b></td>
        <td id="td-participanteContrato-{{ $participanteContrato->id }}-contrato">
            @if($participanteContrato->Contrato_id)
                <a href="/contrato/{{ $participanteContrato->Contrato_id }}">
                    {{ $participanteContrato->contrato->id ?? $participanteContrato->Contrato_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Contrato</span>
            @endif
        </td>
        <td id="btn-participanteContrato-{{ $participanteContrato->id }}-contrato">
            <button onclick="editarCampo('td-participanteContrato-{{ $participanteContrato->id }}-contrato', 'btn-participanteContrato-{{ $participanteContrato->id }}-contrato', 'form-participanteContrato-{{ $participanteContrato->id }}-contrato', 'input-participanteContrato-{{ $participanteContrato->id }}-contrato')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $participanteContrato->Contrato_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-participanteContrato-{{ $participanteContrato->id }}-contrato" colspan="2" style="display:none;">
            @if($contratoCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                    @csrf
                    @method('PUT')
                    <div class="position-relative">
                        <input id="input-participanteContrato-{{ $participanteContrato->id }}-contrato"
                               name="id-contrato"
                               class="form-control form-control-sm"
                               value="{{ $participanteContrato->contrato->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Contrato...">
                        <div id="lista-participanteContrato-{{ $participanteContrato->id }}-Contrato"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select con opción para agregar nuevo --}}
                <div id="form-select-participanteContrato-{{ $participanteContrato->id }}-contrato">
                    <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                        @csrf
                        @method('PUT')
                        <select id="input-participanteContrato-{{ $participanteContrato->id }}-contrato" name="Contrato_id" class="form-select form-select-sm"
                                onchange="if(this.value==='__nuevo__'){
                                    document.getElementById('form-select-participanteContrato-{{ $participanteContrato->id }}-contrato').style.display='none';
                                    document.getElementById('form-buscador-participanteContrato-{{ $participanteContrato->id }}-contrato').style.display='block';
                                }">
                            <option value="">— Seleccionar —</option>
                            @foreach($contratoOptions as $option)
                                <option value="{{ $option->id }}"
                                        {{ $participanteContrato->Contrato_id == $option->id ? 'selected' : '' }}>
                                    {{ $option->id }}
                                </option>
                            @endforeach
                            <option value="__nuevo__">+ Agregar Contrato</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                </div>
                {{-- Buscador para agregar nuevo --}}
                <div id="form-buscador-participanteContrato-{{ $participanteContrato->id }}-contrato" style="display:none;" class="position-relative">
                    <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                        @csrf
                        @method('PUT')
                        <input id="input-buscador-participanteContrato-{{ $participanteContrato->id }}-contrato"
                               name="id-contrato"
                               class="form-control form-control-sm"
                               placeholder="Buscar Contrato..."
                               autocomplete="off">
                        <div id="listaBuscador-participanteContrato-{{ $participanteContrato->id }}-Contrato"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                        <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-link"
                            onclick="document.getElementById('form-buscador-participanteContrato-{{ $participanteContrato->id }}-contrato').style.display='none';
                                     document.getElementById('form-select-participanteContrato-{{ $participanteContrato->id }}-contrato').style.display='block';">
                        Cancelar
                    </button>
                </div>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_contrato] --}}


    {{-- [GEN:START:field_rol] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Rol:</b></td>
        <td id="td-participanteContrato-{{ $participanteContrato->id }}-rol">{{ $participanteContrato->rol }}</td>
        <td id="btn-participanteContrato-{{ $participanteContrato->id }}-rol">
            <button onclick="editarCampo('td-participanteContrato-{{ $participanteContrato->id }}-rol', 'btn-participanteContrato-{{ $participanteContrato->id }}-rol', 'form-participanteContrato-{{ $participanteContrato->id }}-rol', 'input-participanteContrato-{{ $participanteContrato->id }}-rol')">Editar</button>
        </td>
        <td id="form-participanteContrato-{{ $participanteContrato->id }}-rol" colspan="2" style="display:none;">
            <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                @csrf
                @method('PUT')
                <select id="input-participanteContrato-{{ $participanteContrato->id }}-rol" name="rol">
                    <option value="arrendatario" {{ $participanteContrato->rol === 'arrendatario' ? 'selected' : '' }}>arrendatario</option>
                    <option value="arrendador" {{ $participanteContrato->rol === 'arrendador' ? 'selected' : '' }}>arrendador</option>
                    <option value="corredor" {{ $participanteContrato->rol === 'corredor' ? 'selected' : '' }}>corredor</option>
                    <option value="co-arrendatario" {{ $participanteContrato->rol === 'co-arrendatario' ? 'selected' : '' }}>co-arrendatario</option>
                    <option value="co-arrendador" {{ $participanteContrato->rol === 'co-arrendador' ? 'selected' : '' }}>co-arrendador</option>
                </select>
                <input type="submit" value="Modificar">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_rol] --}}


    {{-- [GEN:START:field_monto] @gen:editable --}}
    <tr>
        <td><b>Monto:</b></td>
        <td id="td-participanteContrato-{{ $participanteContrato->id }}-monto">{{ $participanteContrato->monto }}</td>
        <td id="btn-participanteContrato-{{ $participanteContrato->id }}-monto">
            <button onclick="editarCampo('td-participanteContrato-{{ $participanteContrato->id }}-monto', 'btn-participanteContrato-{{ $participanteContrato->id }}-monto', 'form-participanteContrato-{{ $participanteContrato->id }}-monto', 'input-participanteContrato-{{ $participanteContrato->id }}-monto')">Editar</button>
        </td>
        <td id="form-participanteContrato-{{ $participanteContrato->id }}-monto" colspan="2" style="display:none;">
            <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
                @csrf
                @method('PUT')
                <input id="input-participanteContrato-{{ $participanteContrato->id }}-monto" name="monto" type="number" value="{{ $participanteContrato->monto }}">
                <input type="submit" value="Modificar">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_monto] --}}

    </table>

    @push('scripts')
    <script>
    buscador({
        input: '#input-buscador-{{ $participanteContrato->id }}-cliente',
        list:  '#listaBuscador-{{ $participanteContrato->id }}-Cliente',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-buscador-{{ $participanteContrato->id }}-cliente').value = item.texto;
            document.getElementById('input-buscador-{{ $participanteContrato->id }}-cliente').closest('form').submit();
        }
    });

    buscador({
        input: '#input-buscador-{{ $participanteContrato->id }}-contrato',
        list:  '#listaBuscador-{{ $participanteContrato->id }}-Contrato',
        tipo:  'contrato',
        onSelect: function(item) {
            document.getElementById('input-buscador-{{ $participanteContrato->id }}-contrato').value = item.texto;
            document.getElementById('input-buscador-{{ $participanteContrato->id }}-contrato').closest('form').submit();
        }
    });
    </script>
    @endpush
    {{-- [GEN:END:component_table] --}}
</div>
@endsection
