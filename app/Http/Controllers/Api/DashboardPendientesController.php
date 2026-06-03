<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Propiedad;
use App\Services\CobroConceptoFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardPendientesController extends Controller
{
    public function index(Request $request)
    {
        $pagina = max(1, (int) $request->input('pagina', 1));
        $porPagina = max(1, min(100, (int) $request->input('por_pagina', 10)));

        $estadosPendientes = ['Pendiente', 'Vencido'];

        // Get distinct property IDs that have pending cobros
        $propiedadIds = Cobro::whereIn('estado', $estadosPendientes)
            ->whereNotNull('Propiedad_id')
            ->distinct()
            ->pluck('Propiedad_id');

        $total = $propiedadIds->count();
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);

        $propiedadIdsPaginated = $propiedadIds->slice(($pagina - 1) * $porPagina, $porPagina);

        $result = [];

        foreach ($propiedadIdsPaginated as $propiedadId) {
            $propiedad = Propiedad::find($propiedadId);
            if (! $propiedad) {
                continue;
            }

            // Get all pending cobros for this property with eager loading
            $cobros = Cobro::where('Propiedad_id', $propiedadId)
                ->whereIn('estado', $estadosPendientes)
                ->with(['participante_cobros.cliente', 'contrato.participante_contratos'])
                ->get();

            $arrendadorCobros = [];
            $arrendatarioCobros = [];
            $corredorCobros = [];

            foreach ($cobros as $cobro) {
                $deudorPc = $cobro->participante_cobros->firstWhere('rol', 'Deudor');
                $acreedorPc = $cobro->participante_cobros->firstWhere('rol', 'Acreedor');

                $deudorNombre = $deudorPc?->cliente?->nombre ?? 'Desconocido';
                $acreedorNombre = $acreedorPc?->cliente?->nombre ?? 'Desconocido';
                $deudorId = $deudorPc?->Cliente_id;
                $acreedorId = $acreedorPc?->Cliente_id;

                // Determine the role bucket based on deudor's role in the contrato
                $rolBucket = null;
                if ($cobro->contrato) {
                    $participanteContrato = $cobro->contrato->participante_contratos
                        ->firstWhere('Cliente_id', $deudorId);
                    if ($participanteContrato) {
                        $rolBucket = strtolower($participanteContrato->rol);
                    }
                }

                $cobroData = [
                    'id' => $cobro->id,
                    'estado' => $cobro->estado,
                    'tipo' => $cobro->tipo,
                    'monto' => $cobro->monto,
                    'deudor' => $deudorNombre,
                    'deudor_id' => $deudorId,
                    'acreedor' => $acreedorNombre,
                    'acreedor_id' => $acreedorId,
                    'servicio_id' => $cobro->Servicio_id,
                    'fecha_cobro' => $cobro->fecha_cobro ? Carbon::parse($cobro->fecha_cobro)->toIso8601String() : null,
                    'concepto' => CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro ? Carbon::parse($cobro->fecha_cobro) : null),
                ];

                if ($rolBucket === 'arrendador') {
                    $arrendadorCobros[] = $cobroData;
                } elseif ($rolBucket === 'arrendatario') {
                    $arrendatarioCobros[] = $cobroData;
                } elseif ($rolBucket === 'corredor') {
                    $corredorCobros[] = $cobroData;
                } else {
                    // Default: put in arrendador bucket if no contract role found
                    $arrendadorCobros[] = $cobroData;
                }
            }

            $result[] = [
                'id' => $propiedad->id,
                'direccion' => $propiedad->direccion,
                'arrendador' => $arrendadorCobros,
                'arrendatario' => $arrendatarioCobros,
                'corredor' => $corredorCobros,
            ];
        }

        return response()->json([
            'data' => $result,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas,
        ]);
    }
}