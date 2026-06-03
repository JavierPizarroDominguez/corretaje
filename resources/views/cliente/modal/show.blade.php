{{--
    modal/show.blade.php — Contenido para el modal de Cliente

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Cliente', vista: 'vista-cliente-{{ $cliente->id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-cliente-{{ $cliente->id }}">
                @include('cliente.modal.show', ['cliente' => $cliente])
            </div>
        </div>
--}}

<h1>Datos personales</h1>
<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_nombre] @gen:editable --}}
    <tr>
        <td><b>Nombre:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-nombre">
            @if($cliente->nombre)
                {{ $cliente->nombre }}
            @else
                <span class="text-muted fst-italic">Sin Nombre</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-nombre">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-nombre', 'btn-cliente-{{ $cliente->id }}-nombre', 'form-cliente-{{ $cliente->id }}-nombre', 'input-cliente-{{ $cliente->id }}-nombre')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-nombre" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <input id="input-cliente-{{ $cliente->id }}-nombre" name="nombre" type="text" value="{{ $cliente->nombre }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_nombre] --}}


    {{-- [GEN:START:field_fecha_creacion] @gen:editable --}}
    <tr>
        <td><b>Fecha Creacion:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-fecha_creacion">
            @if($cliente->fecha_creacion)
                {{ $cliente->fecha_creacion }}
            @else
                <span class="text-muted fst-italic">Sin Fecha Creacion</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-fecha_creacion">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-fecha_creacion', 'btn-cliente-{{ $cliente->id }}-fecha_creacion', 'form-cliente-{{ $cliente->id }}-fecha_creacion', 'input-cliente-{{ $cliente->id }}-fecha_creacion')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-fecha_creacion" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <input id="input-cliente-{{ $cliente->id }}-fecha_creacion" name="fecha_creacion" type="datetime-local" value="{{ $cliente->fecha_creacion }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_fecha_creacion] --}}


    {{-- [GEN:START:field_rut] @gen:editable --}}
    <tr>
        <td><b>Rut:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-rut">
            @if($cliente->rut)
                {{ $cliente->rut }}
            @else
                <span class="text-muted fst-italic">Sin Rut</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-rut">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-rut', 'btn-cliente-{{ $cliente->id }}-rut', 'form-cliente-{{ $cliente->id }}-rut', 'input-cliente-{{ $cliente->id }}-rut')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-rut" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <input id="input-cliente-{{ $cliente->id }}-rut" name="rut" type="text" value="{{ $cliente->rut }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_rut] --}}


    {{-- [GEN:START:field_email] @gen:editable --}}
    <tr>
        <td><b>Email:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-email">
            @if($cliente->email)
                {{ $cliente->email }}
            @else
                <span class="text-muted fst-italic">Sin Email</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-email">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-email', 'btn-cliente-{{ $cliente->id }}-email', 'form-cliente-{{ $cliente->id }}-email', 'input-cliente-{{ $cliente->id }}-email')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-email" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <input id="input-cliente-{{ $cliente->id }}-email" name="email" type="text" value="{{ $cliente->email }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_email] --}}


    {{-- [GEN:START:field_ocupacion] @gen:editable --}}
    <tr>
        <td><b>Ocupacion:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-ocupacion">
            @if($cliente->ocupacion)
                {{ $cliente->ocupacion }}
            @else
                <span class="text-muted fst-italic">Sin Ocupacion</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-ocupacion">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-ocupacion', 'btn-cliente-{{ $cliente->id }}-ocupacion', 'form-cliente-{{ $cliente->id }}-ocupacion', 'input-cliente-{{ $cliente->id }}-ocupacion')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-ocupacion" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <input id="input-cliente-{{ $cliente->id }}-ocupacion" name="ocupacion" type="text" value="{{ $cliente->ocupacion }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_ocupacion] --}}


    {{-- [GEN:START:field_nacionalidad] @gen:editable @gen:type:relation-fk @gen:related:Nacionalidad --}}
    <tr>
        <td><b>Nacionalidad:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-nacionalidad">
            @if($cliente->Nacionalidad_id)
                <a href="/nacionalidad/{{ $cliente->Nacionalidad_id }}">
                    {{ $cliente->nacionalidad->nombre ?? $cliente->Nacionalidad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Nacionalidad</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-nacionalidad">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-nacionalidad', 'btn-cliente-{{ $cliente->id }}-nacionalidad', 'form-cliente-{{ $cliente->id }}-nacionalidad', 'input-cliente-{{ $cliente->id }}-nacionalidad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $cliente->Nacionalidad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-nacionalidad" colspan="2" style="display:none;">
            @if($nacionalidadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/cliente/{{ $cliente->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-cliente-{{ $cliente->id }}-nacionalidad"
                               name="nombre-nacionalidad"
                               class="form-control form-control-sm"
                               value="{{ $cliente->nacionalidad->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Nacionalidad..."
                               onchange="if(this.value) { document.getElementById('hidden-cliente-{{ $cliente->id }}-nacionalidad').value = ''; }">
                        <div id="lista-cliente-{{ $cliente->id }}-Nacionalidad"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/cliente/{{ $cliente->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-cliente-{{ $cliente->id }}-nacionalidad" name="Nacionalidad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($nacionalidadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $cliente->Nacionalidad_id == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_nacionalidad] --}}


    {{-- [GEN:START:field_estado_civil] @gen:editable @gen:type:enum --}}
    <tr>
        <td><b>Estado Civil:</b></td>
        <td id="td-cliente-{{ $cliente->id }}-estado_civil">
            @if($cliente->estado_civil)
                {{ $cliente->estado_civil }}
            @else
                <span class="text-muted fst-italic">Sin Estado Civil</span>
            @endif
        </td>
        <td id="btn-cliente-{{ $cliente->id }}-estado_civil">
            <button onclick="editarCampo('td-cliente-{{ $cliente->id }}-estado_civil', 'btn-cliente-{{ $cliente->id }}-estado_civil', 'form-cliente-{{ $cliente->id }}-estado_civil', 'input-cliente-{{ $cliente->id }}-estado_civil')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-cliente-{{ $cliente->id }}-estado_civil" colspan="2" style="display:none;">
            <form method="POST" action="/cliente/{{ $cliente->id }}">
                @csrf
                @method('PUT')
                <select id="input-cliente-{{ $cliente->id }}-estado_civil" name="estado_civil" class="form-select form-select-sm">
                    <option value="Soltero" {{ $cliente->estado_civil === 'Soltero' ? 'selected' : '' }}>Soltero</option>
                    <option value="Casado" {{ $cliente->estado_civil === 'Casado' ? 'selected' : '' }}>Casado</option>
                    <option value="Viudo" {{ $cliente->estado_civil === 'Viudo' ? 'selected' : '' }}>Viudo</option>
                    <option value="Divorciado" {{ $cliente->estado_civil === 'Divorciado' ? 'selected' : '' }}>Divorciado</option>
                </select>
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_estado_civil] --}}

</table>
@push('scripts')
<script>
    buscador({
        input: '#input-cliente-{{ $cliente->id }}-nacionalidad',
        list:  '#lista-cliente-{{ $cliente->id }}-Nacionalidad',
        tipo:  'nacionalidad',
        onSelect: function(item) {
            document.getElementById('input-cliente-{{ $cliente->id }}-nacionalidad').value = item.texto;
            document.getElementById('input-cliente-{{ $cliente->id }}-nacionalidad').closest('form').submit();
        }
    });
</script>
@endpush
