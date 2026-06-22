<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\ParticipanteCobro;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TerminarContratoService
{
    public const TIPO_DEVOLUCION_GARANTIA = 'Devolución Garantía Arrendatario';
    public const TIPO_INGRESO_PROPORCIONAL = 'Ingreso Proporcional Renta Arrendatario';
    public const TIPO_EGRESO_PROPORCIONAL = 'Egreso Proporcional Renta Arrendador';

    public function terminar(Contrato $contrato): array
    {
        return DB::transaction(function () use ($contrato): array {
            $contrato = Contrato::query()
                ->whereKey($contrato->id)
                ->lockForUpdate()
                ->with(['unidad.propiedad', 'participante_contratos', 'cobros.participante_cobros'])
                ->firstOrFail();

            $garantia = (int) $contrato->garantia;
            $renta = (int) $contrato->renta;

            $arrendatarioId = $this->participantId($contrato, 'Arrendatario');
            $arrendadorId = $this->participantId($contrato, 'Arrendador');
            $corredorId = $this->participantId($contrato, 'Corredor');

            if (! $arrendatarioId || ! $arrendadorId || ! $corredorId) {
                throw ValidationException::withMessages([
                    'contrato' => ['El contrato debe tener arrendatario, arrendador y corredor para terminarlo.'],
                ]);
            }

            if ($contrato->fecha_termino === null) {
                $contrato->fecha_termino = now();
                $contrato->save();
            }

            $fechaTermino = Carbon::parse($contrato->fecha_termino);
            $montoProporcional = self::calculateProportionalRent($renta, $fechaTermino, (int) $contrato->dia_pago);

            $refundCobro = $this->firstOrCreateCobro($contrato, self::TIPO_DEVOLUCION_GARANTIA, $garantia, 'Devolución de garantía por término de contrato', $arrendadorId, $arrendatarioId);
            $ingresoProporcional = $this->firstOrCreateCobro($contrato, self::TIPO_INGRESO_PROPORCIONAL, $montoProporcional, 'Renta proporcional por término de contrato', $arrendatarioId, $corredorId);
            $egresoProporcional = $this->firstOrCreateCobro($contrato, self::TIPO_EGRESO_PROPORCIONAL, $montoProporcional, 'Renta proporcional por término de contrato', $corredorId, $arrendadorId);

            return [
                'contrato_id' => $contrato->id,
                'fecha_termino' => $contrato->fecha_termino?->toDateTimeString(),
                'monto_devolucion' => $refundCobro->monto,
                'devolucion_cobro_id' => $refundCobro->id,
                'devolucion_estado' => $refundCobro->estado,
                'ingreso_proporcional_cobro_id' => $ingresoProporcional->id,
                'egreso_proporcional_cobro_id' => $egresoProporcional->id,
            ];
        });
    }

    public static function calculateProportionalRent(int $renta, CarbonInterface $fechaTermino, int $diaPago): int
    {
        $daysInMonth = $fechaTermino->daysInMonth;
        $clampedPaymentDay = min(max($diaPago, 1), $daysInMonth);
        $proportionalDays = max(0, $fechaTermino->day - $clampedPaymentDay);

        return (int) round($renta / $daysInMonth * $proportionalDays);
    }

    private function participantId(Contrato $contrato, string $rol): ?int
    {
        return $contrato->participante_contratos
            ->firstWhere('rol', $rol)
            ?->Cliente_id;
    }

    private function firstOrCreateCobro(Contrato $contrato, string $tipo, int $monto, string $detalle, int $deudorId, int $acreedorId): Cobro
    {
        $cobro = Cobro::where('Contrato_id', $contrato->id)
            ->where('tipo', $tipo)
            ->first();

        if (! $cobro) {
            $cobro = Cobro::create([
                'fecha_cobro' => now(),
                'estado' => 'Pendiente',
                'tipo' => $tipo,
                'monto' => $monto,
                'detalle' => $detalle,
                'Contrato_id' => $contrato->id,
                'Servicio_id' => null,
                'Propiedad_id' => $contrato->unidad?->Propiedad_id,
                'Unidad_id' => $contrato->Unidad_id,
            ]);

            $this->createParticipant($cobro, $deudorId, 'Deudor', $monto);
            $this->createParticipant($cobro, $acreedorId, 'Acreedor', $monto);
        }

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

}
