@extends('layouts.app')
@section('title', 'Historial de movimientos')
@section('content')
@include('components.cartola', ['cartola' => $cartola, 'columnasCartola' => $columnasCartola])
@include('components.transacciones-propiedad', ['transacciones' => $transacciones])
<a href="{{ route('fichacliente.show', $cliente->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>
@endsection
