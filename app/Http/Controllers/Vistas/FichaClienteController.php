<?php

namespace App\Http\Controllers\Vistas;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Transaccion;
use Carbon\Carbon;
use App\Models\Contrato;
use App\Models\Servicio;
use App\Models\Propiedad;
use App\Models\Unidad;
use App\Models\ParticipanteCobro;
use App\Services\CobroConceptoFormatter;
use Illuminate\Pagination\LengthAwarePaginator;


class FichaClienteController extends Controller
{
    private const PENDIENTES_PROPERTY_GROUPS_PER_PAGE = 3;
    private const CONTRATOS_PER_PAGE = 5;
    private const ESTADOS_PENDIENTES = ['pendiente', 'vencido', 'incompleto', 'Pendiente', 'Vencido', 'Incompleto'];

    /**
     * Shared base query for Cobro scoped to a client.
     * Used by show(), reparaciones().
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
            ->whereHas('participante_cobros', function ($q) use ($id) {
                $q->where('Cliente_id', $id);
            });
    }

    public function show($id)
    {
        //Cliente
        $cliente = Cliente::with([
                // [GEN:START:eager_load]
                'nacionalidad',
                'cuenta_bancaria',
                'destino_transaccions',
                'origen_transaccions',
                'participante_cobros.cliente',
                'participante_contratos.cliente',
                'propiedades',
                'saldo_clientes',
                'telefonos',
                // [GEN:END:eager_load]
            ])->findOrFail($id);

        //Solo 1 consulta a la BD
        $baseQuery = $this->baseQuery($id);

        //PENDIENTES
        $estadosPendientes = self::ESTADOS_PENDIENTES;
        $pendientesPage = max(1, (int) request()->query('pendientes_page', 1));

        $propiedadIds = (clone $baseQuery)
            ->whereIn('estado', $estadosPendientes)
            ->whereNotNull('Propiedad_id')
            ->orderBy('Propiedad_id')
            ->distinct()
            ->pluck('Propiedad_id');

        $totalPendienteGroups = $propiedadIds->count();
        $propiedadIdsPaginated = $propiedadIds->slice(
            ($pendientesPage - 1) * self::PENDIENTES_PROPERTY_GROUPS_PER_PAGE,
            self::PENDIENTES_PROPERTY_GROUPS_PER_PAGE
        )->values();

        $pendientes = (clone $baseQuery)
            ->whereIn('estado', $estadosPendientes)
            ->whereIn('Propiedad_id', $propiedadIdsPaginated)
            ->orderBy('Propiedad_id')
            ->latest('fecha_cobro')
            ->get();

        $pendientesPaginator = new LengthAwarePaginator(
            $propiedadIdsPaginated,
            $totalPendienteGroups,
            self::PENDIENTES_PROPERTY_GROUPS_PER_PAGE,
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

        // Group cobros by propiedad → unidad → role for grouped card display
        $propiedadesMap = [];
        foreach ($pendientes as $cobro) {
            $propiedadId = $cobro->Propiedad_id;
            if (!$propiedadId && $cobro->contrato && $cobro->contrato->unidad && $cobro->contrato->unidad->propiedad) {
                $propiedadId = $cobro->contrato->unidad->propiedad->id;
            }
            $propKey = $propiedadId ?? 'sin_propiedad';

            if (!isset($propiedadesMap[$propKey])) {
                $direccion = $cobro->contrato?->unidad?->propiedad?->direccion ?? 'Sin propiedad';
                if (!$direccion && $cobro->deudor?->cliente) {
                    $direccion = $cobro->deudor?->cliente?->nombre ?? 'Sin dirección';
                }
                $propiedadesMap[$propKey] = [
                    'id' => $propiedadId,
                    'direccion' => $direccion,
                    'unidad_map' => [],
                ];
            }

            $unidadId = $cobro->Unidad_id ?? 'sin_unidad';
            if (!isset($propiedadesMap[$propKey]['unidad_map'][$unidadId])) {
                $propiedadesMap[$propKey]['unidad_map'][$unidadId] = [
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
            $acreedorId = $acreedorPc?->Cliente_id;

            $cobroData = [
                'id' => $cobro->id,
                'estado' => ucfirst($cobro->estado),
                'tipo' => $cobro->tipo,
                'monto' => $cobro->monto,
                'deudor' => $deudorPc?->cliente?->nombre ?? 'Desconocido',
                'deudor_id' => $deudorId,
                'acreedor' => $acreedorPc?->cliente?->nombre ?? 'Desconocido',
                'acreedor_id' => $acreedorId,
                'servicio_id' => $cobro->Servicio_id,
                'unidad_id' => $cobro->Unidad_id,
                'unidad_nombre' => $cobro->contrato?->unidad?->nombre,
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
                $propiedadesMap[$propKey]['unidad_map'][$unidadId]['arrendador'][] = $cobroData;
            } elseif ($rolBucket === 'arrendatario') {
                $propiedadesMap[$propKey]['unidad_map'][$unidadId]['arrendatario'][] = $cobroData;
            } elseif ($rolBucket === 'corredor') {
                $propiedadesMap[$propKey]['unidad_map'][$unidadId]['corredor'][] = $cobroData;
            } else {
                $propiedadesMap[$propKey]['unidad_map'][$unidadId]['arrendador'][] = $cobroData;
            }
        }

        // Build final grouped structure
        $groupedPendientes = [];
        foreach ($propiedadesMap as $propData) {
            $unidades = array_values($propData['unidad_map']);
            $unidadCount = count($unidades);

            if ($unidadCount > 1) {
                $arrendadorAll = [];
                $arrendatarioAll = [];
                $corredorAll = [];
                foreach ($unidades as $unidad) {
                    $arrendadorAll = array_merge($arrendadorAll, $unidad['arrendador']);
                    $arrendatarioAll = array_merge($arrendatarioAll, $unidad['arrendatario']);
                    $corredorAll = array_merge($corredorAll, $unidad['corredor']);
                }
                $groupedPendientes[] = [
                    'id' => $propData['id'],
                    'direccion' => $propData['direccion'],
                    'unidad_count' => $unidadCount,
                    'unidades' => [],
                    'arrendador' => $arrendadorAll,
                    'arrendatario' => $arrendatarioAll,
                    'corredor' => $corredorAll,
                ];
            } else {
                $unidadData = $unidades[0];
                $groupedPendientes[] = [
                    'id' => $propData['id'],
                    'direccion' => $propData['direccion'],
                    'unidad_count' => 1,
                    'unidades' => [],
                    'arrendador' => $unidadData['arrendador'],
                    'arrendatario' => $unidadData['arrendatario'],
                    'corredor' => $unidadData['corredor'],
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | COUNT y OPTIONS
        |--------------------------------------------------------------------------
        */

        $clienteCount = \App\Models\Cliente::count();

        $clienteOptions = $clienteCount <= config('generator.select_threshold', 15)
            ? \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $contratoCount = \App\Models\Contrato::count();

        $contratoOptions = $contratoCount <= config('generator.select_threshold', 15)
            ? \App\Models\Contrato::orderBy('id')->get(['id'])
            : collect();

        $servicioCount = \App\Models\Servicio::count();

        $servicioOptions = $servicioCount <= config('generator.select_threshold', 15)
            ? \App\Models\Servicio::orderBy('id')->get(['id'])
            : collect();

        $propiedadCount = \App\Models\Propiedad::count();

        $propiedadOptions = $propiedadCount <= config('generator.select_threshold', 15)
            ? \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion'])
            : collect();

        $unidadCount = \App\Models\Unidad::count();

        $unidadOptions = $unidadCount <= config('generator.select_threshold', 15)
            ? \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $nacionalidadCount   = \App\Models\Nacionalidad::count();
        $nacionalidadOptions = \App\Models\Nacionalidad::orderBy('nombre')->get(['id', 'nombre']);

        $participanteCobroCount = ParticipanteCobro::count();

        $participanteCobroOptions = $participanteCobroCount <= config('generator.select_threshold', 15)
            ? ParticipanteCobro::with('cliente')->get(['id', 'Cliente_id', 'Cobro_id', 'deudor_acreedor'])
            : collect();

        /*
        |--------------------------------------------------------------------------
        | TIPOS DE COBRO DISPONIBLES PARA ESTE CLIENTE
        |--------------------------------------------------------------------------
        */
        $tiposCobroDisponibles = collect();

        // Siempre disponibles
        $tiposCobroDisponibles->push('Reparación', 'Extra', 'Devolución');

        // Contratos vigentes query
            $contratosVigentes = Contrato::query()
                ->with([
                    'unidad.propiedad',
                    'arrendador.cliente',
                    'arrendatario.cliente',
                    'corredor.cliente',
                    'participante_contratos.cliente',
                    'cobros' => function ($q) use ($id) {
                        $q->whereIn('estado', self::ESTADOS_PENDIENTES)
                            ->whereHas('participante_cobros', function ($pc) use ($id) {
                                $pc->where('Cliente_id', $id);
                            })
                            ->with('participante_cobros.cliente')
                            ->orderBy('fecha_cobro');
                    },
                ])
                ->whereHas('participante_contratos', function ($q) use ($id) {
                    $q->where('Cliente_id', $id);
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

            // Servicios disponibles en propiedades del cliente
            // Load participante_contratos with nested relationships for property access
            $participantesConContrato = \App\Models\ParticipanteContrato::where('Cliente_id', $cliente->id)
                ->with(['contrato.unidad.propiedad'])
                ->get();
            
            $propiedadesDelCliente = collect($cliente->propiedades ?? []);
            foreach ($participantesConContrato as $pc) {
                if ($pc->contrato && $pc->contrato->unidad && $pc->contrato->unidad->propiedad) {
                    $propiedadesDelCliente->push($pc->contrato->unidad->propiedad);
                }
            }
            $propiedadesDelCliente = $propiedadesDelCliente->unique('id');

            $serviciosTipos = ['Luz', 'Agua', 'Gas', 'Gastos comunes'];
            foreach ($propiedadesDelCliente as $prop) {
                foreach ($serviciosTipos as $servicioTipo) {
                    $tieneServicio = Servicio::where('Propiedad_id', $prop->id)
                        ->where('tipo', $servicioTipo)
                        ->exists();
                    if ($tieneServicio && !$tiposCobroDisponibles->contains($servicioTipo)) {
                        $tiposCobroDisponibles->push($servicioTipo);
                    }
                }
            }

            $tiposCobroDisponibles = $tiposCobroDisponibles->values();

            return view('cliente', compact(
                'cliente',
                'clienteCount',
                'clienteOptions',
                'contratoCount',
                'contratoOptions',
                'servicioCount',
                'servicioOptions',
                'nacionalidadCount',
                'nacionalidadOptions',
                'propiedadCount',
                'propiedadOptions',
                'unidadCount',
                'unidadOptions',
                'contratosVigentes',
                'pendientes',
                'pendientesPaginator',
                'groupedPendientes',
                'participanteCobroCount',
                'participanteCobroOptions',
                'tiposCobroDisponibles',
                'participantOptions',
            ));
    }

    /**
     * GET /cliente/{id}/reparaciones
     * Displays reparaciones table + cartola for a client.
     */
    public function reparaciones($id): \Illuminate\View\View
    {
        $cliente = Cliente::with([
            'nacionalidad',
            'cuenta_bancaria',
            'destino_transaccions',
            'origen_transaccions',
            'participante_cobros.cliente',
            'participante_contratos.cliente',
            'propiedades',
            'saldo_clientes',
            'telefonos',
        ])->findOrFail($id);

        $baseQuery = $this->baseQuery($id);

        $transacciones = Transaccion::query()
            ->whereHas('cobros.participante_cobros', function ($q) use ($id) {
                $q->where('Cliente_id', $id);
            })
            ->with([
                'cobros.deudor.cliente',
                'cobros.acreedor.cliente',
            ])
            ->latest('fecha')
            ->paginate(20, ['*'], 'transacciones_page');

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

        return view('cliente.reparaciones', compact('cliente', 'transacciones', 'cartola', 'columnasCartola'));
    }

    /**
     * GET /cliente/{id}/contratos
     * Displays active contratos for a client.
     */
    public function contratos($id): \Illuminate\View\View
    {
        $cliente = Cliente::with([
            'nacionalidad',
            'cuenta_bancaria',
            'destino_transaccions',
            'origen_transaccions',
            'participante_cobros.cliente',
            'participante_contratos.cliente',
            'propiedades',
            'saldo_clientes',
            'telefonos',
        ])->findOrFail($id);

        $contratosQuery = Contrato::query()
            ->with([
                'unidad.propiedad',
                'arrendador.cliente',
                'arrendatario.cliente',
                'corredor.cliente',
                'participante_contratos.cliente',
                'cobros' => function ($q) use ($id) {
                    $q->whereIn('estado', self::ESTADOS_PENDIENTES)
                        ->whereHas('participante_cobros', function ($pc) use ($id) {
                            $pc->where('Cliente_id', $id);
                        })
                        ->with('participante_cobros.cliente')
                        ->orderBy('fecha_cobro');
                },
            ])
            ->whereHas('participante_contratos', function ($q) use ($id) {
                $q->where('Cliente_id', $id);
            });

        $contratosVigentes = (clone $contratosQuery)
            ->whereNull('fecha_termino')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(self::CONTRATOS_PER_PAGE, ['*'], 'contratos_vigentes_page')
            ->withQueryString();

        $contratosTerminados = (clone $contratosQuery)
            ->whereNotNull('fecha_termino')
            ->orderBy('fecha_termino', 'desc')
            ->paginate(self::CONTRATOS_PER_PAGE, ['*'], 'contratos_terminados_page')
            ->withQueryString();

        return view('cliente.contratos', compact('cliente', 'contratosVigentes', 'contratosTerminados'));
    }
}
