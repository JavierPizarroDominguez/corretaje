@extends('layouts.app')
@section('title', 'Ficha del Cliente')
@section('content')
<div class="row">
    <div class="col-12">
        <h1>{{ $cliente->nombre }}</h1>
    </div>
</div>
@include('components.pendientes', ['pendientes' => $pendientes, 'cliente' => $cliente, 'clienteOptions' => $clienteOptions, 'tiposCobroDisponibles' => $tiposCobroDisponibles])
@include('cliente.modal.show', ['cliente' => $cliente])
@include('components.transacciones-propiedad', ['transacciones' => $transacciones])
<a href="{{ route('cliente.reparaciones', $cliente->id) }}" class="btn btn-sm btn-primary">Historial de movimientos</a>
<a href="{{ route('cliente.contratos', $cliente->id) }}" class="btn btn-sm btn-primary">Contratos</a>
@endsection