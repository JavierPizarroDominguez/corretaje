<?php

namespace App\Traits;

trait HandlesConstraintErrors
{
    protected array $constraintMessages = [
        'chk_rut_formato' => 'El formato del RUT no es válido.',
        'chk_email_formato' => 'El formato del email no es válido.',
        'chk_transaccion_monto' => 'El monto de la transacción no es válido.',
        'chk_cobro_monto' => 'El monto del cobro no es válido.',
        'chk_participante_cobro_monto' => 'El monto del participante en el cobro no es válido.',
        'chk_renta_contrato' => 'El valor de la renta no es válido para este contrato.',
        'chk_dia_pago_contrato' => 'El día de pago no es válido para este contrato.',
        'chk_fecha_inicio_contrato' => 'La fecha de inicio del contrato no es válida.',
        'chk_fecha_termino_contrato' => 'La fecha de término del contrato no es válida.',
        'chk_comision_inicial_contrato' => 'La comisión inicial no es válida para este contrato.',
        'chk_comision_mensual_contrato' => 'La comisión mensual no puede ser mayor a la renta.',
        'chk_datos_administracion' => 'Los datos de administración del contrato no son válidos.',
    ];

    protected function handleConstraintError(\Exception $e): ?string
    {
        $message = $e->getMessage();

        foreach ($this->constraintMessages as $name => $userMessage) {
            if (str_contains($message, $name)) {
                return $userMessage;
            }
        }

        return null;
    }

    protected function autoConstraintMessage(string $constraintName): string
    {
        $name = preg_replace('/^chk_/', '', $constraintName);
        return ucfirst(str_replace('_', ' ', $name)) . '.';
    }

    protected function handleSaveError(
        \Exception $e,
        ?string $fallbackMessage = null
    ): \Illuminate\Http\RedirectResponse {
        $msg = $this->handleConstraintError($e);

        if ($msg !== null) {
            return redirect()->back()->with('error', $msg);
        }

        return redirect()->back()->with(
            'error',
            $fallbackMessage ?? 'Error de validación en los datos ingresados. Verifique los campos e intente nuevamente.'
        );
    }
}
