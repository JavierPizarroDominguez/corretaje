<?php

namespace App\Http\Controllers\Vistas;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use App\Models\Cobro;
use App\Models\Transaccion;
use Carbon\Carbon;
use App\Models\Contrato;
use App\Models\Servicio;
use App\Models\Unidad;
use App\Models\Cliente;
use App\Services\CobroConceptoFormatter;
use Illuminate\Pagination\LengthAwarePaginator;

class FichaPropiedadController extends Controller
{
    private const PENDIENTES_UNIT_GROUPS_PER_PAGE = 3;

    /**
     * Shared base query for Cobro scoped to a propiedad.
     * Scopes via 3 OR conditions:
     *   1. Propiedad_id direct
     *   2. contrato.unidad.propiedad_id
     *   3. servicio.propiedad_id
     */
    private function baseQuery($id)
    {
        return Cobro::query()
            ->with([
                'deudor.cliente',
                'acreedor.cliente',
                'contrato.unidad.propiedad',
                'contrato.participante_contratos',
                'servicio',
                'participante_cobros.cliente',
            ])
            ->where(function ($q) use ($id) {
                $q->where('Propiedad_id', $id)
                    ->orWhereHas('contrato.unidad', function ($q2) use ($id) {
                        $q2->where('Propiedad_id', $id);
                    })
                    ->orWhereHas('servicio', function ($q2) use ($id) {
                        $q2->where('Propiedad_id', $id);
                    });
            });
    }

    public function show($id)
    {
        // Propiedad
        $propiedad = Propiedad::with([
            'cliente',
            'unidad',
            'servicios',
        ])->findOrFail($id);

        // Solo 1 consulta a la BD
        $baseQuery = $this->baseQuery($id);

        // PENDIENTES
        $estadosPendientes = [
            'pendiente',
            'vencido',
            'incompleto',
        ];
        $pendientesPage = max(1, (int) request()->query('pendientes_page', 1));

        $unidadIds = (clone $baseQuery)
            ->whereIn('estado', $estadosPendientes)
            ->whereNotNull('Unidad_id')
            ->orderBy('Unidad_id')
            ->distinct()
            ->pluck('Unidad_id');

        $totalPendienteGroups = $unidadIds->count();
        $unidadIdsPaginated = $unidadIds->slice(
            ($pendientesPage - 1) * self::PENDIENTES_UNIT_GROUPS_PER_PAGE,
            self::PENDIENTES_UNIT_GROUPS_PER_PAGE
        )->values();

        $pendientes = (clone $baseQuery)
            ->whereIn('estado', $estadosPendientes)
            ->whereIn('Unidad_id', $unidadIdsPaginated)
            ->orderBy('Unidad_id')
            ->latest('fecha_cobro')
            ->get();

        $pendientesPaginator = new LengthAwarePaginator(
            $unidadIdsPaginated,
            $totalPendienteGroups,
            self::PENDIENTES_UNIT_GROUPS_PER_PAGE,
            $pendientesPage,
            [
                'path' => request()->url(),
                'pageName' => 'pendientes_page',
                'query' => request()->except('pendientes_page'),
            ]
        );

        foreach ($pendientes as $value) {
            if (!$value->tipo) {
                $value->concepto = 'Sin tipo';
                continue;
            }
            $value->concepto = CobroConceptoFormatter::format($value->tipo, $value->fecha_cobro);
        }

        // Group cobros by unidad → role for grouped card display
        $unidadesMap = [];
        foreach ($pendientes as $cobro) {
            $unidadId = $cobro->Unidad_id ?? 'sin_unidad';
            if (!isset($unidadesMap[$unidadId])) {
                $unidadesMap[$unidadId] = [
                    'id' => $unidadId === 'sin_unidad' ? null : $unidadId,
                    'nombre' => $unidadId === 'sin_unidad' ? 'Sin unidad' : ($cobro->contrato?->unidad?->nombre ?? 'Sin unidad'),
                    'arrendador' => [],
                    'arrendatario' => [],
                    'corredor' => [],
                ];
            }

            // Build cobro data for Blade
            $deudorPc = $cobro->participante_cobros->firstWhere('rol', 'Deudor');
            $acreedorPc = $cobro->participante_cobros->firstWhere('rol', 'Acreedor');
            $deudorId = $deudorPc?->Cliente_id;

            $cobroData = [
                'id' => $cobro->id,
                'estado' => ucfirst($cobro->estado),
                'tipo' => $cobro->tipo,
                'monto' => $cobro->monto,
                'deudor' => $deudorPc?->cliente?->nombre ?? 'Desconocido',
                'deudor_id' => $deudorId,
                'acreedor' => $acreedorPc?->cliente?->nombre ?? 'Desconocido',
                'acreedor_id' => $acreedorPc?->Cliente_id,
                'servicio_id' => $cobro->Servicio_id,
                'fecha_cobro' => $cobro->fecha_cobro ? $cobro->fecha_cobro->toISOString() : null,
                'concepto' => $cobro->concepto,
            ];

            // Bucket by role
            $rolBucket = null;
            if ($cobro->contrato) {
                $participanteContrato = $cobro->contrato->participante_contratos
                    ->firstWhere('Cliente_id', $deudorId);
                if ($participanteContrato) {
                    $rolBucket = strtolower($participanteContrato->rol);
                }
            }

            if ($rolBucket === 'arrendador') {
                $unidadesMap[$unidadId]['arrendador'][] = $cobroData;
            } elseif ($rolBucket === 'arrendatario') {
                $unidadesMap[$unidadId]['arrendatario'][] = $cobroData;
            } elseif ($rolBucket === 'corredor') {
                $unidadesMap[$unidadId]['corredor'][] = $cobroData;
            } else {
                $unidadesMap[$unidadId]['arrendador'][] = $cobroData;
            }
        }

        $groupedPendientes = array_values($unidadesMap);
        $showUnidadColumn = Unidad::where('Propiedad_id', $id)->count() > 1;

        /*
        |--------------------------------------------------------------------------
        | TRANSACCIONES
        |--------------------------------------------------------------------------
        */

        $transacciones = Transaccion::query()
            ->whereHas('cobros', function ($q) use ($id) {
                $q->where('Propiedad_id', $id)
                    ->orWhereHas('contrato.unidad', function ($q2) use ($id) {
                        $q2->where('Propiedad_id', $id);
                    })
                    ->orWhereHas('servicio', function ($q2) use ($id) {
                        $q2->where('Propiedad_id', $id);
                    });
            })
            ->with([
                'cobros.deudor.cliente',
                'cobros.acreedor.cliente',
            ])
            ->latest('fecha')
            ->paginate(20, ['*'], 'transacciones_page');

        /*
        |--------------------------------------------------------------------------
        | COUNT y OPTIONS
        |--------------------------------------------------------------------------
        */

        $clienteCount = Cliente::count();

        $clienteOptions = $clienteCount <= config('generator.select_threshold', 15)
            ? Cliente::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $contratoCount = Contrato::count();

        $contratoOptions = $contratoCount <= config('generator.select_threshold', 15)
            ? Contrato::orderBy('id')->get(['id'])
            : collect();

        $servicioCount = Servicio::count();

        $servicioOptions = $servicioCount <= config('generator.select_threshold', 15)
            ? Servicio::orderBy('id')->get(['id'])
            : collect();

        $propiedadCount = Propiedad::count();

        $propiedadOptions = $propiedadCount <= config('generator.select_threshold', 15)
            ? Propiedad::orderBy('direccion')->get(['id', 'direccion'])
            : collect();

        $unidadCount = Unidad::count();

        $unidadOptions = $unidadCount <= config('generator.select_threshold', 15)
            ? Unidad::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        /*
        |--------------------------------------------------------------------------
        | TIPOS DE COBRO DISPONIBLES PARA ESTA PROPIEDAD
        |--------------------------------------------------------------------------
        */
        $tiposCobroDisponibles = collect();

        // Siempre disponibles
        $tiposCobroDisponibles->push('Reparación', 'Extra', 'Devolución');

        // Contratos query — propiedades que tienen unidad con contrato vigente
        $contratosVigentes = Contrato::query()
            ->with([
                'unidad.propiedad',
                'arrendador',
                'arrendatario',
                'participante_contratos.cliente',
            ])
            ->whereHas('unidad', function ($q) use ($id) {
                $q->where('Propiedad_id', $id);
            })
            ->where(function ($query) {
                $query->whereNull('fecha_termino')
                      ->orWhere('fecha_termino', '>', now());
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // CUSTOM: derive participant options from active contracts for ficha cobro modal
        $participantOptions = collect();
        foreach ($contratosVigentes as $contrato) {
            foreach ($contrato->participante_contratos as $pc) {
                if ($pc->cliente) {
                    $participantOptions->push($pc->cliente);
                }
            }
        }
        $participantOptions = $participantOptions->unique('id')->sortBy('nombre')->values();

        // Si tiene contratos vigentes: renta, comisiones, garantías, aseo
        $tieneContratosVigentes = $contratosVigentes->isNotEmpty();
        if ($tieneContratosVigentes) {
            $tiposCobroDisponibles->push(
                'Ingreso Renta Arrendatario',
                'Egreso Renta Arrendador',
                'Comision inicial arrendador',
                'Comision inicial arrendatario',
                'Comision Mensual',
                'Ingreso Garantía Arrendatario',
                'Egreso Garantía Arrendador',
                'Devolución Garantía Arrendatario',
                'Aseo Final'
            );
        }

        // Servicios disponibles en esta propiedad
        $serviciosTipos = ['Luz', 'Agua', 'Gas', 'Gastos comunes'];
        foreach ($propiedad->servicios as $servicio) {
            if (in_array($servicio->tipo, $serviciosTipos) && !$tiposCobroDisponibles->contains($servicio->tipo)) {
                $tiposCobroDisponibles->push($servicio->tipo);
            }
        }

        $tiposCobroDisponibles = $tiposCobroDisponibles->values();

        return view('propiedad', compact(
            'propiedad',
            'clienteCount',
            'clienteOptions',
            'contratoCount',
            'contratoOptions',
            'servicioCount',
            'servicioOptions',
            'propiedadCount',
            'propiedadOptions',
            'unidadCount',
            'unidadOptions',
            'contratosVigentes',
            'pendientes',
            'pendientesPaginator',
            'groupedPendientes',
            'showUnidadColumn',
            'transacciones',
            'tiposCobroDisponibles',
            'participantOptions',
        ));
    }

    /**
     * GET /propiedad/{id}/reparaciones
     * Displays reparaciones table + cartola for a propiedad.
     */
    public function reparaciones($id): \Illuminate\View\View
    {
        $propiedad = Propiedad::with([
            'cliente',
            'unidad',
            'servicios',
        ])->findOrFail($id);

        $baseQuery = $this->baseQuery($id);

        $reparaciones = (clone $baseQuery)
            ->whereIn('tipo', [
                'Reparación',
                'Devolución',
                'Extra',
            ])
            ->latest('fecha_cobro')
            ->paginate(20);

        $cobrosCartola = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereIn('tipo', [
                    'Ingreso Renta Arrendatario',
                    'Egreso Renta Arrendador',
                ])
                ->orWhereHas('servicio', function ($q) {
                    $q->whereIn('tipo', [
                        'Luz',
                        'Agua',
                        'Gas',
                        'Gastos Comunes',
                        'Aseo Municipal',
                    ]);
                });
            })
            ->orderBy('fecha_cobro')
            ->get();

        $cartola = [];
        $columnasCartola = [];

        foreach ($cobrosCartola as $cobro) {
            $year = $cobro->fecha_cobro->year;
            $mes = $cobro->fecha_cobro->translatedFormat('F');
            $unidad = $cobro->contrato?->unidad?->id ?? 'Sin unidad';

            $columna = null;
            switch ($cobro->tipo) {
                case 'Ingreso Renta Arrendatario':
                    $columna = 'Ingreso Renta';
                    break;
                case 'Egreso Renta Arrendador':
                    $columna = 'Egreso Renta';
                    break;
                default:
                    $columna = $cobro->servicio?->nombre ?? null;
                    break;
            }

            if (!$columna) continue;

            if (!isset($cartola[$unidad][$year][$mes])) {
                $cartola[$unidad][$year][$mes] = [];
            }

            $cartola[$unidad][$year][$mes][$columna] = $cobro;
            $columnasCartola[$columna] = true;
        }

        $columnasOrden = ['Ingreso Renta', 'Egreso Renta', 'Luz', 'Agua', 'Gas', 'Gastos Comunes', 'Aseo Municipal'];
        $columnasCartola = array_values(array_intersect($columnasOrden, array_keys($columnasCartola)));

        return view('propiedad.reparaciones', compact('propiedad', 'reparaciones', 'cartola', 'columnasCartola'));
    }

    /**
     * GET /propiedad/{id}/contratos
     * Displays active contratos for a propiedad.
     */
    public function contratos($id): \Illuminate\View\View
    {
        $propiedad = Propiedad::with([
            'cliente',
            'unidad',
            'servicios',
        ])->findOrFail($id);

        $contratosVigentes = Contrato::query()
            ->with([
                'unidad.propiedad',
                'arrendador',
                'arrendatario',
            ])
            ->whereHas('unidad', function ($q) use ($id) {
                $q->where('Propiedad_id', $id);
            })
            ->where(function ($query) {
                $query->whereNull('fecha_termino')
                      ->orWhere('fecha_termino', '>', now());
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return view('propiedad.contratos', compact('propiedad', 'contratosVigentes'));
    }
}
