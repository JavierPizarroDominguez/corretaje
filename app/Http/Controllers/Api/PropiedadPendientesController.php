<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Propiedad;
use App\Models\Unidad;
use App\Services\CobroConceptoFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PropiedadPendientesController extends Controller
{
    private const MAX_UNIT_GROUPS_PER_PAGE = 3;

    /**
     * GET /api/propiedad/{id}/pendientes
     * Returns cobros grouped by unidad, then bucketed by role.
     * Includes all three estados: Pendiente, Vencido, Incompleto.
     */
    public function index(Request $request, int $id)
    {
        $pagina = max(1, (int) $request->input('pagina', 1));
        $porPagina = max(1, min(self::MAX_UNIT_GROUPS_PER_PAGE, (int) $request->input('por_pagina', self::MAX_UNIT_GROUPS_PER_PAGE)));

        $estadosPendientes = ['Pendiente', 'Vencido', 'Incompleto'];
        $unidadCount = Unidad::where('Propiedad_id', $id)->count();

        // Get distinct unidad IDs that have pending cobros for this propiedad
        // Scoped via: Propiedad_id direct OR contrato.unidad.propiedad_id OR servicio.propiedad_id
        $unidadIds = Cobro::query()
            ->where(function ($q) use ($id) {
                $q->where('Propiedad_id', $id)
                    ->orWhereHas('contrato.unidad', fn($q2) => $q2->where('Propiedad_id', $id))
                    ->orWhereHas('servicio', fn($q2) => $q2->where('Propiedad_id', $id));
            })
            ->whereIn('estado', $estadosPendientes)
            ->whereNotNull('Unidad_id')
            ->orderBy('Unidad_id')
            ->distinct()
            ->pluck('Unidad_id');

        $total = $unidadIds->count();
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);

        $unidadIdsPaginated = $unidadIds->slice(($pagina - 1) * $porPagina, $porPagina);

        $result = [];

        foreach ($unidadIdsPaginated as $unidadId) {
            $unidad = Unidad::find($unidadId);
            if (! $unidad) {
                continue;
            }

            // Get all pending cobros for this unidad (scoped to this propiedad)
            $cobros = Cobro::query()
                ->where(function ($q) use ($id, $unidadId) {
                    $q->where('Propiedad_id', $id)
                        ->orWhereHas('contrato.unidad', fn($q2) => $q2->where('Propiedad_id', $id))
                        ->orWhereHas('servicio', fn($q2) => $q2->where('Propiedad_id', $id));
                })
                ->where('Unidad_id', $unidadId)
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
                'id' => $unidad->id,
                'direccion' => $unidad->nombre,
                'unidad_id' => $unidad->id,
                'unidad_nombre' => $unidad->nombre,
                'arrendador' => $arrendadorCobros,
                'arrendatario' => $arrendatarioCobros,
                'corredor' => $corredorCobros,
            ];
        }

        return response()->json([
            'show_unidad' => $unidadCount > 1,
            'unidad_count' => $unidadCount,
            'data' => $result,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas,
        ]);
    }
}
