@extends('layouts.app')
@section('title', 'Ficha de la Propiedad')
@section('content')
<div class="row">
    <div class="col-12">
        @include('components.titulo-propiedad', ['propiedad' => $propiedad, 'clienteCount' => $clienteCount ?? 0, 'clienteOptions' => $clienteOptions ?? collect()])
    </div>
</div>
@include('components.pendientes-propiedad', ['pendientes' => $pendientes, 'propiedad' => $propiedad, 'propiedadOptions' => $propiedadOptions, 'tiposCobroDisponibles' => $tiposCobroDisponibles])
@include('components.transacciones-propiedad', ['transacciones' => $transacciones])
<a href="{{ route('propiedad.reparaciones', $propiedad->id) }}" class="btn btn-sm btn-primary">Historial de movimientos</a>
<a href="{{ route('propiedad.contratos', $propiedad->id) }}" class="btn btn-sm btn-primary">Contratos</a>
@endsection