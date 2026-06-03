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


class FichaClienteController extends Controller
{
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
                'servicio',
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
            ->whereHas('cobros.participante_cobros', function ($q) use ($id) {
                $q->where('Cliente_id', $id);
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
            | VIEW
            |--------------------------------------------------------------------------
            */

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
                    'arrendador',
                    'arrendatario',
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
                'transacciones',
                'participanteCobroCount',
                'participanteCobroOptions',
                'tiposCobroDisponibles',
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

        return view('cliente.reparaciones', compact('cliente', 'reparaciones', 'cartola', 'columnasCartola'));
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

        $contratosVigentes = Contrato::query()
            ->with([
                'unidad.propiedad',
                'arrendador',
                'arrendatario',
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

        return view('cliente.contratos', compact('cliente', 'contratosVigentes'));
    }
}
