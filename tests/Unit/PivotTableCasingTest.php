<?php

namespace Tests\Unit;

use App\Models\Cobro;
use App\Models\Transaccion;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ReflectionClass;
use Tests\TestCase;

class PivotTableCasingTest extends TestCase
{
    /**
     * Task 1.1: Verify Transaccion::cobros() uses the correct pivot table casing.
     */
    public function test_transaccion_cobros_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Transaccion())->cobros();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Transaccion_Cobro', $pivotTable);
    }

    /**
     * Task 2.1: Verify Cobro::transaccions() uses the correct pivot table casing.
     */
    public function test_cobro_transaccions_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Cobro())->transaccions();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Transaccion_Cobro', $pivotTable);
    }

    /**
     * Triangulation: verify both relationships use the same pivot table name.
     */
    public function test_both_relationships_use_same_pivot_table(): void
    {
        $transaccionRelation = (new Transaccion())->cobros();
        $cobroRelation = (new Cobro())->transaccions();

        $reflection = new ReflectionClass($transaccionRelation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);

        $cobroReflection = new ReflectionClass($cobroRelation);
        $cobroTableProperty = $cobroReflection->getProperty('table');
        $cobroTableProperty->setAccessible(true);

        $transaccionTable = $tableProperty->getValue($transaccionRelation);
        $cobroTable = $cobroTableProperty->getValue($cobroRelation);

        $this->assertSame($transaccionTable, $cobroTable);
        $this->assertSame('Transaccion_Cobro', $transaccionTable);
    }
}
