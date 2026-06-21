@extends('layouts.app')
@section('title', 'Contratos')
@section('content')
@include('components.contratos', ['contratosVigentes' => $contratosVigentes, 'contratosTerminados' => $contratosTerminados, 'clienteContextId' => $cliente->id])
<a href="{{ route('fichacliente.show', $cliente->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>
@endsection
