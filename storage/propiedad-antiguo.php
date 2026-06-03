<?php

namespace App\Http\Controllers;

use App\Models\Propiedad;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Cobro;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PropiedadController extends Controller
{
    public function index()
    {
        return Propiedad::query()->paginate();
    }

    public function store(Request $request)
    {
        return Propiedad::create($request->all());
    }

    public function show($id)
    {
        $propiedad = Propiedad::with([
            'cliente',
            'unidad',
            'servicios',
        ])->findOrFail($id);

        $pendientes = Cobro::query()
            ->with([
                'deudor.cliente',
                'acreedor.cliente',
                'contrato.unidad',
                'servicio',
            ])
            ->whereIn('estado', [
                'Pendiente',
                'Vencido',
                'Incompleto',
            ])
            ->where(function ($q) use ($id) {

                $q->whereHas('contrato.unidad', function ($q2) use ($id) {
                    $q2->where('Propiedad_id', $id);
                })

                ->orWhereHas('servicio', function ($q2) use ($id) {
                    $q2->where('Propiedad_id', $id);
                })

                ->orWhere('Propiedad_id', $id);

            })
            ->latest('fecha_cobro')
            ->paginate(20, ['*'], 'pendientes_page');

        $reparaciones = Cobro::query()
            ->with([
                'deudor.cliente',
                'acreedor.cliente',
                'contrato.unidad',
            ])
            ->whereIn('tipo', [
                'Reparación',
                'Devolución',
                'Extra',
            ])
            ->where(function ($q) use ($id) {

                $q->whereHas('contrato.unidad', function ($q2) use ($id) {
                    $q2->where('Propiedad_id', $id);
                })

                ->orWhere('Propiedad_id', $id);

            })
            ->latest('fecha_cobro')
            ->paginate(20, ['*'], 'reparaciones_page');

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
        | COLUMNAS CARTOLA
        |--------------------------------------------------------------------------
        */

        $contratoVigente = $propiedad->unidad?->contratoVigente;

        $serviciosCartola = collect();

        if ($contratoVigente?->administracion) {

            $serviciosCartola->push(
                'Ingreso Renta Arrendatario'
            );

            if ($contratoVigente->arrendador_id != 1) {

                $serviciosCartola->push(
                    'Egreso Renta Arrendador'
                );
            }
        }

       /*
|--------------------------------------------------------------------------
| TIPOS DISPONIBLES EN COBROS
|--------------------------------------------------------------------------
*/

$tiposDisponibles = Cobro::query()

    ->where(function ($q) use ($id) {

        $q->whereHas('contrato.unidad', function ($q2) use ($id) {
            $q2->where('Propiedad_id', $id);
        })

        ->orWhereHas('servicio', function ($q2) use ($id) {
            $q2->where('Propiedad_id', $id);
        })

        ->orWhere('Propiedad_id', $id);

    })

    ->pluck('tipo')

    ->unique();

/*
|--------------------------------------------------------------------------
| SERVICIOS CON COBROS
|--------------------------------------------------------------------------
*/

if ($tiposDisponibles->contains('Luz')) {
    $serviciosCartola->push('Luz');
}

if ($tiposDisponibles->contains('Agua')) {
    $serviciosCartola->push('Agua');
}

if ($tiposDisponibles->contains('Gas')) {
    $serviciosCartola->push('Gas');
}

if ($tiposDisponibles->contains('Gastos Comunes')) {
    $serviciosCartola->push('Gastos Comunes');
}

        /*
        |--------------------------------------------------------------------------
        | COBROS CARTOLA
        |--------------------------------------------------------------------------
        */

        $cobrosCartola = Cobro::query()

            ->with([
                'servicio',
            ])

            ->where(function ($q) use ($id) {

                $q->whereHas('contrato.unidad', function ($q2) use ($id) {
                    $q2->where('Propiedad_id', $id);
                })

                ->orWhereHas('servicio', function ($q2) use ($id) {
                    $q2->where('Propiedad_id', $id);
                })

                ->orWhere('Propiedad_id', $id);

            })

            ->orderBy('fecha_cobro')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | CONSTRUIR CARTOLA
        |--------------------------------------------------------------------------
        */

        $cartola = collect();

        foreach ($cobrosCartola as $cobro) {

            $mes = ucfirst(
                $cobro->fecha_cobro
                    ?->locale('es')
                    ->translatedFormat('F Y')
            );

            $tipo = $cobro->tipo;

            if (!$serviciosCartola->contains($tipo)) {
                continue;
            }

           if (!$cartola->has($mes)) {
                $cartola->put($mes, collect());
            }

            $fila = $cartola->get($mes);

            $fila->put($tipo, $cobro);

            $cartola->put($mes, $fila);
        }

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view('propiedad', compact(
            'propiedad',
            'pendientes',
            'reparaciones',
            'transacciones',
            'contratoVigente',
            'serviciosCartola',
            'cartola',
        ));
    }

    public function update(Request $request, $id)
    {
        try {

            $propiedad = Propiedad::findOrFail($id);

            if ($request->has('direccion')) {
                $propiedad->direccion = $request->direccion;
            }

            if ($request->has('propietario')) {

                $cliente = Cliente::firstOrCreate([
                    'nombre' => trim($request->propietario)
                ]);

                $propiedad->propietario = $cliente->id;
            }

            $propiedad->save();

            return redirect()->back()
                ->with('success', 'Propiedad actualizada correctamente');

        } catch (\Exception $e) {

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}