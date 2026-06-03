@extends('layouts.app')
@section('title', 'Reparaciones y Cartola')
@section('content')
@include('components.reparaciones-propiedad', ['reparaciones' => $reparaciones])
@include('components.cartola', ['cartola' => $cartola, 'columnasCartola' => $columnasCartola])
<a href="{{ route('fichacliente.show', $cliente->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>
@endsection
