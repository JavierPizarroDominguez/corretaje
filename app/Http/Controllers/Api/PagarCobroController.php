<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagarCobroRequest;
use App\Models\Cobro;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PagarCobroController extends Controller
{
    /**
     * Register a payment against a Cobro, transitioning it to Pagado.
     * Creates Transaccion + TransaccionCobro pivot within a DB transaction.
     */
    public function pagar(PagarCobroRequest $request): JsonResponse
    {
        $cobro = Cobro::find($request->validated('cobro_id'));

        if (! $cobro) {
            return response()->json(['message' => 'Cobro no encontrado'], 404);
        }

        if (! in_array($cobro->estado, ['Pendiente', 'Vencido'], true)) {
            return response()->json([
                'errors' => ['cobro_id' => ['El cobro no se encuentra en estado pendiente o vencido']],
            ], 422);
        }

        return DB::transaction(function () use ($cobro) {
            $deudor = $cobro->participante_cobros()->where('rol', 'Deudor')->first();
            $acreedor = $cobro->participante_cobros()->where('rol', 'Acreedor')->first();

            $deudorId = $deudor?->Cliente_id;
            $acreedorId = $acreedor?->Cliente_id;

            // Resolve OrigenTransaccion: tipo='Cuenta Bancaria', Cliente_id=deudor
            $origen = OrigenTransaccion::firstOrCreate(
                ['tipo' => 'Cuenta Bancaria', 'Cliente_id' => $deudorId, 'Cuenta_Bancaria_id' => null]
            );

            // Resolve DestinoTransaccion
            $destinoData = $cobro->Servicio_id === null
                ? ['tipo' => 'Cuenta Bancaria', 'Cliente_id' => $acreedorId, 'Servicio_id' => null, 'Cuenta_Bancaria_id' => null]
                : ['tipo' => 'Empresa de servicio', 'Cliente_id' => null, 'Servicio_id' => $cobro->Servicio_id, 'Cuenta_Bancaria_id' => null];

            $destino = DestinoTransaccion::firstOrCreate($destinoData);

            $transaccion = Transaccion::create([
                'monto' => $cobro->monto,
                'fecha' => now(),
                'Origen_Transaccion_id' => $origen->id,
                'Destino_Transaccion_id' => $destino->id,
            ]);

            TransaccionCobro::create([
                'Transaccion_id' => $transaccion->id,
                'Cobro_id' => $cobro->id,
                'monto_pagado' => $cobro->monto,
            ]);

            $cobro->estado = 'Pagado';
            $cobro->save();

            return response()->json([
                'transaccion_id' => $transaccion->id,
                'cobro_estado' => 'Pagado',
            ]);
        });
    }
}
