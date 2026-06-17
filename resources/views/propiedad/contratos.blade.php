@extends('layouts.app')
@section('title', 'Contratos Vigentes')
@section('content')
@include('components.contratos', ['contratosVigentes' => $contratosVigentes, 'propiedadContextId' => $propiedad->id])
<a href="{{ route('propiedad.ficha', $propiedad->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>
@endsection
