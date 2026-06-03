<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HandlesConstraintErrorsTest extends TestCase
{
    private function makeTrait(): object
    {
        return new class {
            use \App\Traits\HandlesConstraintErrors;

            public function runHandleConstraintError(\Exception $e): ?string
            {
                return $this->handleConstraintError($e);
            }

            public function runAutoConstraintMessage(string $constraintName): string
            {
                return $this->autoConstraintMessage($constraintName);
            }
        };
    }

    public function test_known_constraint_returns_readable_message(): void
    {
        $trait = $this->makeTrait();
        $exception = new \Exception('SQL error: chk_rut_formato violation');

        $result = $trait->runHandleConstraintError($exception);

        $this->assertNotNull($result);
        $this->assertSame('El formato del RUT no es válido.', $result);
    }

    public function test_unknown_constraint_returns_null(): void
    {
        $trait = $this->makeTrait();
        $exception = new \Exception('Some other database error');

        $result = $trait->runHandleConstraintError($exception);

        $this->assertNull($result);
    }

    public function test_different_known_constraint_returns_own_message(): void
    {
        $trait = $this->makeTrait();
        $exception = new \Exception('check constraint chk_renta_contrato');

        $result = $trait->runHandleConstraintError($exception);

        $this->assertNotNull($result);
        $this->assertSame('El valor de la renta no es válido para este contrato.', $result);
    }

    public function test_constraint_name_in_middle_of_message(): void
    {
        $trait = $this->makeTrait();
        $exception = new \Exception('Column "monto" violates CHECK constraint "chk_transaccion_monto" in table "transaccion"');

        $result = $trait->runHandleConstraintError($exception);

        $this->assertNotNull($result);
        $this->assertSame('El monto de la transacción no es válido.', $result);
    }

    public function test_auto_constraint_message_generates_readable_text(): void
    {
        $trait = $this->makeTrait();

        $result = $trait->runAutoConstraintMessage('chk_comision_mensual_contrato');

        $this->assertSame('Comision mensual contrato.', $result);
    }
}
