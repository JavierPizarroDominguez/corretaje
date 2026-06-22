<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\DescuentoGarantia;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;
use App\Models\ParticipanteCobro;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GarantiaRefundService
{
    public function finalize(Cobro $refundCobro, array $discounts): array
    {
        return DB::transaction(function () use ($refundCobro, $discounts): array {
            $refundCobro = Cobro::query()
                ->whereKey($refundCobro->id)
                ->lockForUpdate()
                ->with(['contrato.participante_contratos', 'participante_cobros'])
                ->firstOrFail();

            $this->assertFinalizable($refundCobro);

            $baseAmount = (int) ($refundCobro->contrato?->garantia ?? $refundCobro->monto ?? 0);
            $totalDiscounts = collect($discounts)->sum(fn (array $discount): int => (int) ($discount['monto'] ?? 0));

            if ($totalDiscounts > $baseAmount) {
                throw ValidationException::withMessages([
                    'descuentos' => ['Los descuentos no pueden superar la garantía a devolver.'],
                ]);
            }

            foreach ($discounts as $discount) {
                $discountCobro = $this->createDiscountCobro($refundCobro, $discount);

                DescuentoGarantia::create([
                    'Cobro_Devolucion_id' => $refundCobro->id,
                    'Cobro_Descuento_id' => $discountCobro->id,
                ]);
            }

            $finalAmount = max(0, $baseAmount - $totalDiscounts);
            $refundCobro->monto = $finalAmount;
            $refundCobro->estado = 'Pagado';
            $refundCobro->save();

            $transactionId = null;
            if ($finalAmount > 0) {
                $transactionId = $this->createRefundTransaction($refundCobro, $finalAmount);
            }

            return [
                'cobro_id' => $refundCobro->id,
                'cobro_estado' => 'Pagado',
                'monto_devolucion' => $finalAmount,
                'transaccion_id' => $transactionId,
                'descuentos_count' => count($discounts),
            ];
        });
    }

    private function assertFinalizable(Cobro $refundCobro): void
    {
        if ($refundCobro->tipo !== TerminarContratoService::TIPO_DEVOLUCION_GARANTIA) {
            throw ValidationException::withMessages([
                'cobro' => ['El cobro no corresponde a una devolución de garantía.'],
            ]);
        }

        $hasFinalizationData = DescuentoGarantia::where('Cobro_Devolucion_id', $refundCobro->id)->exists()
            || TransaccionCobro::where('Cobro_id', $refundCobro->id)->exists();

        if ($refundCobro->estado === 'Pagado' || $hasFinalizationData) {
            throw ValidationException::withMessages([
                'cobro' => ['La devolución de garantía ya fue finalizada.'],
            ]);
        }

        if ($refundCobro->estado !== 'Pendiente') {
            throw ValidationException::withMessages([
                'cobro' => ['La devolución de garantía debe estar pendiente.'],
            ]);
        }
    }

    private function createDiscountCobro(Cobro $refundCobro, array $discount): Cobro
    {
        $amount = (int) ($discount['monto'] ?? 0);
        $contract = $refundCobro->contrato;
        $arrendatarioId = $this->participantId($refundCobro, 'Arrendatario');
        $arrendadorId = $this->participantId($refundCobro, 'Arrendador');

        $discountCobro = Cobro::create([
            'fecha_cobro' => now(),
            'estado' => 'Pagado',
            'tipo' => $discount['concepto'],
            'monto' => $amount,
            'detalle' => $discount['detalle'] ?? null,
            'Contrato_id' => $refundCobro->Contrato_id,
            'Servicio_id' => null,
            'Propiedad_id' => $refundCobro->Propiedad_id,
            'Unidad_id' => $refundCobro->Unidad_id,
        ]);

        if ($arrendatarioId) {
            $this->createParticipant($discountCobro, $arrendatarioId, 'Deudor', $amount);
        }

        if ($arrendadorId) {
            $this->createParticipant($discountCobro, $arrendadorId, 'Acreedor', $amount);
        }

        return $discountCobro;
    }

    private function createRefundTransaction(Cobro $refundCobro, int $amount): int
    {
        $deudorId = $refundCobro->participante_cobros->firstWhere('rol', 'Deudor')?->Cliente_id;
        $acreedorId = $refundCobro->participante_cobros->firstWhere('rol', 'Acreedor')?->Cliente_id;

        $origen = OrigenTransaccion::firstOrCreate([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $deudorId,
            'Cuenta_Bancaria_id' => null,
        ]);

        $destino = DestinoTransaccion::firstOrCreate([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $acreedorId,
            'Servicio_id' => null,
            'Cuenta_Bancaria_id' => null,
        ]);

        $transaction = Transaccion::create([
            'monto' => $amount,
            'fecha' => now(),
            'Origen_Transaccion_id' => $origen->id,
            'Destino_Transaccion_id' => $destino->id,
        ]);

        TransaccionCobro::create([
            'Transaccion_id' => $transaction->id,
            'Cobro_id' => $refundCobro->id,
            'monto_pagado' => $amount,
        ]);

        return $transaction->id;
    }

    private function participantId(Cobro $refundCobro, string $role): ?int
    {
        return $refundCobro->contrato?->participante_contratos
            ->firstWhere('rol', $role)
            ?->Cliente_id;
    }

    private function createParticipant(Cobro $cobro, int $clienteId, string $rol, int $amount): void
    {
        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $clienteId,
            'rol' => $rol,
            'monto' => $amount,
        ]);
    }
}
