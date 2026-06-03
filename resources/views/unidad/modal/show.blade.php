{{--
    modal/show.blade.php — Contenido para el modal de Unidad

    Uso desde la vista padre:
        <button type="button" class="btn btn-primary btn-sm"
                onclick="abrirModal({titulo: 'Unidad', vista: 'vista-unidad-{{ $unidad->id }}'})">
            Ver
        </button>

        <div class="d-none">
            <div id="vista-unidad-{{ $unidad->id }}">
                @include('unidad.modal.show', ['unidad' => $unidad])
            </div>
        </div>
--}}

<table class="table table-bordered table-sm mb-3">
    {{-- [GEN:START:field_nombre] @gen:editable --}}
    <tr>
        <td><b>Nombre:</b></td>
        <td id="td-unidad-{{ $unidad->id }}-nombre">{{ $unidad->nombre }}</td>
        <td id="btn-unidad-{{ $unidad->id }}-nombre">
            <button onclick="editarCampo('td-unidad-{{ $unidad->id }}-nombre', 'btn-unidad-{{ $unidad->id }}-nombre', 'form-unidad-{{ $unidad->id }}-nombre', 'input-unidad-{{ $unidad->id }}-nombre')" class="btn btn-sm btn-outline-secondary">Editar</button>
        </td>
        <td id="form-unidad-{{ $unidad->id }}-nombre" colspan="2" style="display:none;">
            <form method="POST" action="/unidad/{{ $unidad->id }}">
                @csrf
                @method('PUT')
                <input id="input-unidad-{{ $unidad->id }}-nombre" name="nombre" type="text" value="{{ $unidad->nombre }}">
                <input type="submit" value="Modificar" class="btn btn-sm btn-primary">
            </form>
        </td>
    </tr>
    {{-- [GEN:END:field_nombre] --}}


    {{-- [GEN:START:field_propiedad] @gen:editable @gen:type:relation-fk @gen:related:Propiedad --}}
    <tr>
        <td><b>Propiedad:</b></td>
        <td id="td-unidad-{{ $unidad->id }}-propiedad">
            @if($unidad->Propiedad_id)
                <a href="/propiedad/{{ $unidad->Propiedad_id }}">
                    {{ $unidad->propiedad->direccion ?? $unidad->Propiedad_id }}
                </a>
            @else
                <span class="text-muted fst-italic">Sin Propiedad</span>
            @endif
        </td>
        <td id="btn-unidad-{{ $unidad->id }}-propiedad">
            <button onclick="editarCampo('td-unidad-{{ $unidad->id }}-propiedad', 'btn-unidad-{{ $unidad->id }}-propiedad', 'form-unidad-{{ $unidad->id }}-propiedad', 'input-unidad-{{ $unidad->id }}-propiedad')"
                    class="btn btn-sm btn-outline-secondary">
                {{ $unidad->Propiedad_id ? 'Editar' : 'Agregar' }}
            </button>
        </td>
        <td id="form-unidad-{{ $unidad->id }}-propiedad" colspan="2" style="display:none;">
            @if($propiedadCount > config('generator.select_threshold', 15))
                {{-- Buscador: muchos registros --}}
                <form method="POST" action="/unidad/{{ $unidad->id }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="position-relative">
                        <input id="input-unidad-{{ $unidad->id }}-propiedad"
                               name="direccion-propiedad"
                               class="form-control form-control-sm"
                               value="{{ $unidad->propiedad->direccion ?? '' }}"
                               autocomplete="off"
                               placeholder="Buscar Propiedad..."
                               onchange="if(this.value) { document.getElementById('hidden-unidad-{{ $unidad->id }}-propiedad').value = ''; }">
                        <div id="lista-unidad-{{ $unidad->id }}-Propiedad"
                             class="list-group position-absolute w-100"
                             style="z-index:1000;"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Guardar</button>
                </form>
            @else
                {{-- Select simple --}}
                <form method="POST" action="/unidad/{{ $unidad->id }}">
                    @csrf
                    @method('PUT')
                    
                    <select id="input-unidad-{{ $unidad->id }}-propiedad" name="Propiedad_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($propiedadOptions as $option)
                            <option value="{{ $option->id }}"
                                    {{ $unidad->Propiedad_id == $option->id ? 'selected' : '' }}>
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

</table>
<div class="d-flex gap-2">
    <a href="/unidad/{{ $unidad->id }}"
       class="btn btn-primary btn-sm">Ver completo</a>
</div>

@push('scripts')
<script>
    buscador({
        input: '#input-unidad-{{ $unidad->id }}-propiedad',
        list:  '#lista-unidad-{{ $unidad->id }}-Propiedad',
        tipo:  'propiedad',
        onSelect: function(item) {
            document.getElementById('input-unidad-{{ $unidad->id }}-propiedad').value = item.texto;
            document.getElementById('input-unidad-{{ $unidad->id }}-propiedad').closest('form').submit();
        }
    });
</script>
@endpush
