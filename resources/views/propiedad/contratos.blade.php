@extends('layouts.app')
@section('title', 'Contratos')
@section('content')
@include('components.contratos', ['contratosVigentes' => $contratosVigentes, 'contratosTerminados' => $contratosTerminados, 'propiedadContextId' => $propiedad->id])
<a href="{{ route('propiedad.ficha', $propiedad->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>
@endsection
