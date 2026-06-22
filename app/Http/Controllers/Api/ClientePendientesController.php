<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Propiedad;
use App\Services\CobroConceptoFormatter;
use App\Services\GarantiaRefundMetadata;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientePendientesController extends Controller
{
    private const MAX_PROPERTY_GROUPS_PER_PAGE = 3;

    /**
     * GET /api/cliente/{id}/pendientes
     * Returns cobros grouped by propiedad, then by unidad (if >1), then bucketed by role.
     * Includes all three estados: Pendiente, Vencido, Incompleto.
     */
    public function index(Request $request, int $id)
    {
        $pagina = max(1, (int) $request->input('pagina', 1));
        $porPagina = max(1, min(self::MAX_PROPERTY_GROUPS_PER_PAGE, (int) $request->input('por_pagina', self::MAX_PROPERTY_GROUPS_PER_PAGE)));

        $estadosPendientes = ['Pendiente', 'Vencido', 'Incompleto'];

        // Get distinct propiedad IDs that have pending cobros for this client
        $propiedadIds = Cobro::query()
            ->whereHas('participante_cobros', fn($q) => $q->where('Cliente_id', $id))
            ->whereIn('estado', $estadosPendientes)
            ->whereNotNull('Propiedad_id')
            ->orderBy('Propiedad_id')
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

            // Get all pending cobros for this propiedad (scoped to this client)
            $cobros = Cobro::query()
                ->whereHas('participante_cobros', fn($q) => $q->where('Cliente_id', $id))
                ->where('Propiedad_id', $propiedadId)
                ->whereIn('estado', $estadosPendientes)
                ->with(['participante_cobros.cliente', 'contrato.participante_contratos', 'contrato.unidad', 'unidad'])
                ->get();

            // Group cobros by unidad
            $unidadesMap = [];
            foreach ($cobros as $cobro) {
                $unidadId = $cobro->Unidad_id ?? 'sin_unidad';
                if (! isset($unidadesMap[$unidadId])) {
                    $unidadesMap[$unidadId] = [
                        'id' => $unidadId === 'sin_unidad' ? null : $unidadId,
                        'nombre' => $unidadId === 'sin_unidad' ? 'Sin unidad' : ($cobro->contrato?->unidad?->nombre ?? 'Sin unidad'),
                        'arrendador' => [],
                        'arrendatario' => [],
                        'corredor' => [],
                    ];
                }

                $cobroData = $this->buildCobroData($cobro);
                $this->bucketCobroByRole($cobroData, $cobro, $unidadesMap[$unidadId]);
            }

            $arrendadorCobros = [];
            $arrendatarioCobros = [];
            $corredorCobros = [];

            foreach ($unidadesMap as $unidadData) {
                $arrendadorCobros = array_merge($arrendadorCobros, $unidadData['arrendador']);
                $arrendatarioCobros = array_merge($arrendatarioCobros, $unidadData['arrendatario']);
                $corredorCobros = array_merge($corredorCobros, $unidadData['corredor']);
            }

            $result[] = [
                'id' => $propiedad->id,
                'direccion' => $propiedad->direccion,
                'unidad_count' => count($unidadesMap),
                'unidades' => [],
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

    /**
     * Build thecobro data array for API response.
     */
    protected function buildCobroData(Cobro $cobro): array
    {
        $deudorPc = $cobro->participante_cobros->firstWhere('rol', 'Deudor');
        $acreedorPc = $cobro->participante_cobros->firstWhere('rol', 'Acreedor');

        $deudorNombre = $deudorPc?->cliente?->nombre ?? 'Desconocido';
        $acreedorNombre = $acreedorPc?->cliente?->nombre ?? 'Desconocido';
        $deudorId = $deudorPc?->Cliente_id;
        $acreedorId = $acreedorPc?->Cliente_id;

        return [
            'id' => $cobro->id,
            'estado' => $cobro->estado,
            'tipo' => $cobro->tipo,
            'monto' => $cobro->monto,
            'deudor' => $deudorNombre,
            'deudor_id' => $deudorId,
            'acreedor' => $acreedorNombre,
            'acreedor_id' => $acreedorId,
            'servicio_id' => $cobro->Servicio_id,
            'unidad_id' => $cobro->Unidad_id,
            'unidad_nombre' => $cobro->unidad?->nombre ?? $cobro->contrato?->unidad?->nombre,
            'fecha_cobro' => $cobro->fecha_cobro ? Carbon::parse($cobro->fecha_cobro)->toIso8601String() : null,
            'concepto' => CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro ? Carbon::parse($cobro->fecha_cobro) : null),
        ] + GarantiaRefundMetadata::forCobro($cobro);
    }

    /**
     * Bucket a cobro into the appropriate role array based on deudor's contrato role.
     */
    protected function bucketCobroByRole(array $cobroData, Cobro $cobro, array &$targetUnidad): void
    {
        $deudorPc = $cobro->participante_cobros->firstWhere('rol', 'Deudor');
        $deudorId = $deudorPc?->Cliente_id;

        $rolBucket = null;
        if ($cobro->contrato) {
            $participanteContrato = $cobro->contrato->participante_contratos
                ->firstWhere('Cliente_id', $deudorId);
            if ($participanteContrato) {
                $rolBucket = strtolower($participanteContrato->rol);
            }
        }

        if ($rolBucket === 'arrendador') {
            $targetUnidad['arrendador'][] = $cobroData;
        } elseif ($rolBucket === 'arrendatario') {
            $targetUnidad['arrendatario'][] = $cobroData;
        } elseif ($rolBucket === 'corredor') {
            $targetUnidad['corredor'][] = $cobroData;
        } else {
            // Default: put in arrendador bucket if no contract role found
            $targetUnidad['arrendador'][] = $cobroData;
        }
    }
}
