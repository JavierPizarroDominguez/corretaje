<?php

namespace App\Services;

use App\Models\Cobro;
use Carbon\Carbon;

class GarantiaRefundMetadata
{
    public static function forCobro(Cobro $cobro): array
    {
        $isGuaranteeRefund = $cobro->tipo === TerminarContratoService::TIPO_DEVOLUCION_GARANTIA;
        $contrato = $cobro->contrato;
        $fechaTermino = $contrato?->fecha_termino ? Carbon::parse($contrato->fecha_termino) : null;
        $deadline = $fechaTermino?->copy()->addDays(30);
        $elapsedDays = $fechaTermino ? max(0, $fechaTermino->diffInDays(now(), false)) : null;

        return [
            'is_guarantee_refund' => $isGuaranteeRefund,
            'contrato_id' => $isGuaranteeRefund ? $cobro->Contrato_id : null,
            'fecha_termino' => $isGuaranteeRefund && $fechaTermino ? $fechaTermino->toIso8601String() : null,
            'plazo_restante_dias' => $isGuaranteeRefund && $elapsedDays !== null ? max(0, 30 - $elapsedDays) : null,
            'refund_deadline' => $isGuaranteeRefund && $deadline ? $deadline->toIso8601String() : null,
            'base_monto_devolucion' => $isGuaranteeRefund ? (int) ($contrato?->garantia ?? $cobro->monto ?? 0) : null,
        ];
    }
}
