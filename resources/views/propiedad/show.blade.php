@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Propiedad #{{ $propiedad->id }}</h2>
        <div>
            <a href="/propiedad/{{ $propiedad->id }}/edit" class="btn btn-secondary btn-sm">Editar</a>
            <a href="/propiedad" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>
    </div>

    {{-- [GEN:START:component_table] --}}
    <table class="table table-bordered">
    {{-- [GEN:START:field_direccion] @gen:editable --}}
    <tr>
        <td><b>Direccion:</b></td>
        <td id="td-propiedad-{{ $propiedad->id }}-direccion">{{ $propiedad->direccion }}</td>
        <td id="btn-propiedad-{{ $propiedad->id }}-direccion">
            <button onclick="editarCampo('td-propiedad-{{ $propiedad->id }}-direccion', 'btn-propiedad-{{ $propiedad->id }}-direccion', 'form-propiedad-{{ $propiedad->id }}-direccion', 'input-propiedad-{{ $propiedad->id }}-direccion')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-propiedad-{{ $propiedad->id }}-direccion" colspan="2" style="display:none;">
            <form method="POST" action="/propiedad/{{ $propiedad->id }}">
                @csrf
                @method('PUT')
                <input id="input-propiedad-{{ $propiedad->id }}-direccion" name="direccion" type="text" value="{{ $propiedad->direccion }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_direccion] --}}


    {{-- [GEN:START:field_propietario] @gen:editable @gen:type:relation-fk @gen:related:Cliente --}}
    <tr>
        <td><b>Propietario:</b></td>
        <td id="td-propiedad-{{ $propiedad->id }}-propietario">
            @if($propiedad->propietario)
                <a href="/cliente/{{ $propiedad->propietario }}">
                    {{ $propiedad->propietario->nombre ?? $propiedad->propietario }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Propietario</span>
            @endif
        </td>
        <td id="btn-propiedad-{{ $propiedad->id }}-propietario">
            <button onclick="editarCampo('td-propiedad-{{ $propiedad->id }}-propietario', 'btn-propiedad-{{ $propiedad->id }}-propietario', 'form-propiedad-{{ $propiedad->id }}-propietario', 'input-propiedad-{{ $propiedad->id }}-propietario')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $propiedad->propietario ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-propiedad-{{ $propiedad->id }}-propietario" colspan="2" style="display:none;">
            @if($clienteCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/propiedad/{{ $propiedad->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-propiedad-{{ $propiedad->id }}-propietario"
                               name="nombre-propietario"
                               class="form-control form-control-sm"
                               value="{{ $propiedad->propietario->nombre ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Propietario..."
                               onchange="if(this.value) { document.getElementById('hidden-propiedad-{{ $propiedad->id }}-propietario').value = ''; }">
                        <div id="lista-propiedad-{{ $propiedad->id }}-Propietario"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/propiedad/{{ $propiedad->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-propiedad-{{ $propiedad->id }}-propietario" name="propietario" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($clienteOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $propiedad->propietario == $option->id ? 'selected' : '' }}>
                                {{ $option->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @endif
        </td>
    </tr>
    {{-- [GEN:END:field_propietario] --}}

    </table>

    @push('scripts')
    <script>
    buscador({
        input: '#input-propiedad-{{ $propiedad->id }}-propietario',
        list:  '#lista-propiedad-{{ $propiedad->id }}-Propietario',
        tipo:  'cliente',
        onSelect: function(item) {
            document.getElementById('input-propiedad-{{ $propiedad->id }}-propietario').value = item.texto;
            document.getElementById('input-propiedad-{{ $propiedad->id }}-propietario').closest('form').submit();
        }
    });
    </script>
    @endpush
    {{-- [GEN:END:component_table] --}}
</div>
@endsection
