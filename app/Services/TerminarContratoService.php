<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\DescuentoGarantia;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;
use App\Models\ParticipanteCobro;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TerminarContratoService
{
    public function terminar(Contrato $contrato, array $descuentos): array
    {
        return DB::transaction(function () use ($contrato, $descuentos): array {
            $contrato = Contrato::query()
                ->whereKey($contrato->id)
                ->lockForUpdate()
                ->with(['unidad.propiedad', 'participante_contratos'])
                ->firstOrFail();

            $garantia = (int) $contrato->garantia;
            $totalDescuentos = $this->sumDiscounts($descuentos);

            if ($totalDescuentos > $garantia) {
                throw ValidationException::withMessages([
                    'descuentos' => ['El total de descuentos no puede superar la garantía.'],
                ]);
            }

            $arrendatarioId = $this->participantId($contrato, 'Arrendatario');
            $arrendadorId = $this->participantId($contrato, 'Arrendador');

            if (! $arrendatarioId || ! $arrendadorId) {
                throw ValidationException::withMessages([
                    'contrato' => ['El contrato debe tener arrendatario y arrendador para terminarlo.'],
                ]);
            }

            $contrato->fecha_termino = now();
            $contrato->save();

            $discountCobros = [];
            foreach ($descuentos as $descuento) {
                $discountCobros[] = $this->createDiscountCobro(
                    $contrato,
                    $descuento,
                    $arrendatarioId,
                    $arrendadorId
                );
            }

            $montoDevolucion = $garantia - $totalDescuentos;
            $refundCobro = $this->createRefundCobro($contrato, $montoDevolucion, $arrendadorId, $arrendatarioId);

            foreach ($discountCobros as $discountCobro) {
                DescuentoGarantia::create([
                    'Cobro_Devolucion_id' => $refundCobro->id,
                    'Cobro_Descuento_id' => $discountCobro->id,
                ]);
            }

            $transaccion = null;
            if ($montoDevolucion > 0) {
                $transaccion = $this->createRefundTransaction($refundCobro, $arrendadorId, $arrendatarioId);
            }

            return [
                'contrato_id' => $contrato->id,
                'fecha_termino' => $contrato->fecha_termino?->toDateTimeString(),
                'total_descuentos' => $totalDescuentos,
                'monto_devolucion' => $montoDevolucion,
                'devolucion_cobro_id' => $refundCobro->id,
                'devolucion_estado' => $refundCobro->estado,
                'transaccion_id' => $transaccion?->id,
            ];
        });
    }

    private function sumDiscounts(array $descuentos): int
    {
        return collect($descuentos)->sum(function (array $descuento): int {
            return (int) $descuento['monto'];
        });
    }

    private function participantId(Contrato $contrato, string $rol): ?int
    {
        return $contrato->participante_contratos
            ->firstWhere('rol', $rol)
            ?->Cliente_id;
    }

    private function createDiscountCobro(Contrato $contrato, array $descuento, int $arrendatarioId, int $arrendadorId): Cobro
    {
        $monto = (int) $descuento['monto'];
        $cobro = Cobro::create([
            'fecha_cobro' => now(),
            'estado' => 'Pagado',
            'tipo' => $descuento['concepto'],
            'monto' => $monto,
            'detalle' => $descuento['detalle'] ?? null,
            'Contrato_id' => $contrato->id,
            'Servicio_id' => null,
            'Propiedad_id' => $contrato->unidad?->Propiedad_id,
            'Unidad_id' => $contrato->Unidad_id,
        ]);

        $this->createParticipant($cobro, $arrendatarioId, 'Deudor', $monto);
        $this->createParticipant($cobro, $arrendadorId, 'Acreedor', $monto);

        return $cobro;
    }

    private function createRefundCobro(Contrato $contrato, int $monto, int $arrendadorId, int $arrendatarioId): Cobro
    {
        $cobro = Cobro::create([
            'fecha_cobro' => now(),
            'estado' => $monto > 0 ? 'Pendiente' : 'Pagado',
            'tipo' => 'Devolución Garantía Arrendatario',
            'monto' => $monto,
            'detalle' => 'Devolución de garantía por término de contrato',
            'Contrato_id' => $contrato->id,
            'Servicio_id' => null,
            'Propiedad_id' => $contrato->unidad?->Propiedad_id,
            'Unidad_id' => $contrato->Unidad_id,
        ]);

        $this->createParticipant($cobro, $arrendadorId, 'Deudor', $monto);
        $this->createParticipant($cobro, $arrendatarioId, 'Acreedor', $monto);

        return $cobro;
    }

    private function createParticipant(Cobro $cobro, int $clienteId, string $rol, int $monto): void
    {
        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $clienteId,
            'rol' => $rol,
            'monto' => $monto,
        ]);
    }

    private function createRefundTransaction(Cobro $refundCobro, int $arrendadorId, int $arrendatarioId): Transaccion
    {
        $origen = OrigenTransaccion::firstOrCreate([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $arrendadorId,
            'Cuenta_Bancaria_id' => null,
        ]);

        $destino = DestinoTransaccion::firstOrCreate([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $arrendatarioId,
            'Servicio_id' => null,
            'Cuenta_Bancaria_id' => null,
        ]);

        $transaccion = Transaccion::create([
            'monto' => $refundCobro->monto,
            'fecha' => now(),
            'Origen_Transaccion_id' => $origen->id,
            'Destino_Transaccion_id' => $destino->id,
        ]);

        TransaccionCobro::create([
            'Transaccion_id' => $transaccion->id,
            'Cobro_id' => $refundCobro->id,
            'monto_pagado' => $refundCobro->monto,
        ]);

        return $transaccion;
    }
}
