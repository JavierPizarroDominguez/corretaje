@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Editar participantecontrato</h2>

    <form method="POST" action="/participante_contrato/{{ $participanteContrato->id }}">
        @csrf
        @method('PUT')
        <div>
            <label>Rol</label>
            <input type="select" name="rol" value="{{ old('rol', $participanteContrato->rol) }}">
            @error('rol') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label>Monto</label>
            <input type="number" name="monto" value="{{ old('monto', $participanteContrato->monto) }}">
            @error('monto') <span>{{ $message }}</span> @enderror
        </div>
        <button type="submit">Guardar</button>
        <a href="/participante_contrato/{{ $participanteContrato->id }}">Cancelar</a>
    </form>
</div>
@endsection
