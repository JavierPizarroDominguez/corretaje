{{--
    modal/show.blade.php — Contenido para el modal de ParticipanteCobro

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'ParticipanteCobro', vista: 'vista-participanteCobro-{{ $participanteCobro->cliente_id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-participanteCobro-{{ $participanteCobro->cliente_id }}">
                @include('participante_cobro.modal.show', ['participanteCobro' => $participanteCobro])
            </div>
        </div>
--}}

<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_cliente] @gen:editable @gen:type:relation-fk @gen:related:Cliente --}}
    <tr>
        <td><b>Cliente:</b></td>
        <td id="td-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente">
            @if($participanteCobro->Cliente_id)
                <a href="/cliente/{{ $participanteCobro->Cliente_id }}">
                    {{ $participanteCobro->cliente->nombre ?? $participanteCobro->Cliente_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Cliente</span>
            @endif
        </td>
        <td id="btn-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente">
            <button onclick="editarCampo('td-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente', 'btn-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente', 'form-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente', 'input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $participanteCobro->Cliente_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente" colspan="2" style="display:none;">
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente"
                               name="nombre-cliente"
                               class="form-control form-control-sm"
                               value="{{ $participanteCobro->cliente->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Cliente..."
                               onchange="if(this.value) { document.getElementById('hidden-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente').value = ''; }">
                        <div id="lista-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-Cliente"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente" name="Cliente_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($clienteOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $participanteCobro->Cliente_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_cliente] --}}


    {{-- [GEN:START:field_cobro] @gen:editable @gen:type:relation-fk @gen:related:Cobro --}}
    <tr>
        <td><b>Cobro:</b></td>
        <td id="td-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro">
            @if($participanteCobro->Cobro_id)
                <a href="/cobro/{{ $participanteCobro->Cobro_id }}">
                    {{ $participanteCobro->cobro->id ?? $participanteCobro->Cobro_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Cobro</span>
            @endif
        </td>
        <td id="btn-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro">
            <button onclick="editarCampo('td-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro', 'btn-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro', 'form-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro', 'input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $participanteCobro->Cobro_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro" colspan="2" style="display:none;">
            @if($cobroCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro"
                               name="id-cobro"
                               class="form-control form-control-sm"
                               value="{{ $participanteCobro->cobro->id ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Cobro..."
                               onchange="if(this.value) { document.getElementById('hidden-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro').value = ''; }">
                        <div id="lista-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-Cobro"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro" name="Cobro_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($cobroOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $participanteCobro->Cobro_id == $option->id ? 'selected' : '' }}>
                                {{ $option->id }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_cobro] --}}


    {{-- [GEN:START:field_monto] @gen:editable --}}
    <tr>
        <td><b>Monto:</b></td>
        <td id="td-participanteCobro-{{ $participanteCobro->cliente_id }}-monto">{{ $participanteCobro->monto }}</td>
        <td id="btn-participanteCobro-{{ $participanteCobro->cliente_id }}-monto">
            <button onclick="editarCampo('td-participanteCobro-{{ $participanteCobro->cliente_id }}-monto', 'btn-participanteCobro-{{ $participanteCobro->cliente_id }}-monto', 'form-participanteCobro-{{ $participanteCobro->cliente_id }}-monto', 'input-participanteCobro-{{ $participanteCobro->cliente_id }}-monto')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-participanteCobro-{{ $participanteCobro->cliente_id }}-monto" colspan="2" style="display:none;">
            <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                @csrf
                @method('PUT')
                <input id="input-participanteCobro-{{ $participanteCobro->cliente_id }}-monto" name="monto" type="number" value="{{ $participanteCobro->monto }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_monto] --}}


    {{-- [GEN:START:field_rol] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Rol:</b></td>
        <td id="td-participanteCobro-{{ $participanteCobro->cliente_id }}-rol">{{ $participanteCobro->rol }}</td>
        <td id="btn-participanteCobro-{{ $participanteCobro->cliente_id }}-rol">
            <button onclick="editarCampo('td-participanteCobro-{{ $participanteCobro->cliente_id }}-rol', 'btn-participanteCobro-{{ $participanteCobro->cliente_id }}-rol', 'form-participanteCobro-{{ $participanteCobro->cliente_id }}-rol', 'input-participanteCobro-{{ $participanteCobro->cliente_id }}-rol')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-participanteCobro-{{ $participanteCobro->cliente_id }}-rol" colspan="2" style="display:none;">
            <form method="POST" action="/participante_cobro/{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}">
                @csrf
                @method('PUT')
                <select id="input-participanteCobro-{{ $participanteCobro->cliente_id }}-rol" name="rol" class="form-select form-select-sm">
                    <option value="Deudor" {{ $participanteCobro->rol === 'Deudor' ? 'selected' : '' }}>Deudor</option>
                    <option value="Acreedor" {{ $participanteCobro->rol === 'Acreedor' ? 'selected' : '' }}>Acreedor</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_rol] --}}

</table>
<div class="d-flex gap-2">
    <a href="/participante_cobro/{{ $participanteCobro->cliente_id }}"
       class="btn btn-primary btn-sm">Ver completo</a>
</div>

@push('scripts')
<script>
    buscador({
        input: '#input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente',
        list:  '#lista-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-Cliente',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente').value = item.texto;
            document.getElementById('input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cliente').closest('form').submit();
        }
    });

    buscador({
        input: '#input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro',
        list:  '#lista-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-Cobro',
        tipo:  'cobro',
        onSelect: function(item) {
            document.getElementById('input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro').value = item.texto;
            document.getElementById('input-participanteCobro-{{ $participanteCobro->cliente->id }}/{{ $participanteCobro->cobro->id }}-cobro').closest('form').submit();
        }
    });
</script>
@endpush
