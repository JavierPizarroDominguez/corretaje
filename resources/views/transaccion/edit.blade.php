@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Editar transaccion</h2>

    <form method="POST" action="/transaccion/{{ $transaccion->id }}">
        @csrf
        @method('PUT')
        <div>
            <label>Monto</label>
            <input type="number" name="monto" value="{{ old('monto', $transaccion->monto) }}">
            @error('monto') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label>Fecha</label>
            <input type="datetime-local" name="fecha" value="{{ old('fecha', $transaccion->fecha) }}">
            @error('fecha') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label>Url Comprobante</label>
            <input type="text" name="url_comprobante" value="{{ old('url_comprobante', $transaccion->url_comprobante) }}">
            @error('url_comprobante') <span>{{ $message }}</span> @enderror
        </div>
        <button type="submit">Guardar</button>
        <a href="/transaccion/{{ $transaccion->id }}">Cancelar</a>
    </form>
</div>
@endsection
