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

class FichaPropiedadController extends Controller
{
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
                'servicio',
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
        $pendientes = (clone $baseQuery)
            ->whereIn('estado', [
                'pendiente',
                'vencido',
                'incompleto',
            ])
            ->latest('fecha_cobro')
            ->paginate(10, ['*'], 'pendientes_page');

        foreach ($pendientes as $value) {
            if (!$value->tipo) {
                $value->concepto = 'Sin tipo';
                continue;
            }
            if ($value->deudor || $value->acreedor) {
                switch ($value->tipo) {
                case 'Ingreso Renta Arrendatario':
                    $value->concepto = 'Cobrar Renta ' . ($value->deudor?->cliente?->nombre ?? 'Sin deudor');
                    break;
                case 'Ingreso Garantía Arrendatario':
                    $value->concepto = 'Cobrar Garantía ' . ($value->deudor?->cliente?->nombre ?? 'Sin deudor');
                    break;
                case 'Comision inicial arrendador':
                case 'Comision inicial arrendatario':
                    $value->concepto = 'Cobrar Comisión inicial ' . ($value->deudor?->cliente?->nombre ?? 'Sin deudor');
                    break;
                case 'Egreso Renta Arrendador':
                    $value->concepto = 'Transferir Renta ' . ($value->acreedor?->cliente?->nombre ?? 'Sin acreedor');
                    break;
                case 'Egreso Garantía Arrendador':
                    $value->concepto = 'Transferir Garantía ' . ($value->acreedor?->cliente?->nombre ?? 'Sin acreedor');
                    break;
                default:
                    $value->concepto = $value->tipo;
                    break;
                }
            }
            else{
                $value->concepto = $value->tipo;
            }
        }

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
            'transacciones',
            'tiposCobroDisponibles',
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