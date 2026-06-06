<?php

namespace Tests\Unit;

use App\Models\Clausula;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\Telefono;
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

    /**
     * Task 2.1: Verify Cliente::telefonos() uses the correct pivot table casing.
     */
    public function test_cliente_telefonos_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Cliente())->telefonos();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Telefono_Cliente', $pivotTable);
    }

    /**
     * Task 2.2: Verify Telefono::clientes() uses the correct pivot table casing.
     */
    public function test_telefono_clientes_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Telefono())->clientes();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Telefono_Cliente', $pivotTable);
    }

    /**
     * Task 2.3: Verify Contrato::clausulas() uses the correct pivot table casing.
     */
    public function test_contrato_clausulas_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Contrato())->clausulas();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Clausula_Contrato', $pivotTable);
    }

    /**
     * Task 2.4: Verify Clausula::contratos() uses the correct pivot table casing.
     */
    public function test_clausula_contratos_uses_correct_pivot_table_casing(): void
    {
        $relation = (new Clausula())->contratos();

        $this->assertInstanceOf(BelongsToMany::class, $relation);

        $reflection = new ReflectionClass($relation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $pivotTable = $tableProperty->getValue($relation);

        $this->assertEquals('Clausula_Contrato', $pivotTable);
    }

    /**
     * Task 2.5: Triangulation — Cliente and Telefono must agree on Telefono_Cliente.
     */
    public function test_cliente_telefono_agree_on_pivot_table(): void
    {
        $clienteRelation = (new Cliente())->telefonos();
        $telefonoRelation = (new Telefono())->clientes();

        $reflection = new ReflectionClass($clienteRelation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);

        $telefonoReflection = new ReflectionClass($telefonoRelation);
        $telefonoTableProperty = $telefonoReflection->getProperty('table');
        $telefonoTableProperty->setAccessible(true);

        $clienteTable = $tableProperty->getValue($clienteRelation);
        $telefonoTable = $telefonoTableProperty->getValue($telefonoRelation);

        $this->assertSame($clienteTable, $telefonoTable);
        $this->assertSame('Telefono_Cliente', $clienteTable);
    }

    /**
     * Task 2.6: Triangulation — Contrato and Clausula must agree on Clausula_Contrato.
     */
    public function test_contrato_clausula_agree_on_pivot_table(): void
    {
        $contratoRelation = (new Contrato())->clausulas();
        $clausulaRelation = (new Clausula())->contratos();

        $reflection = new ReflectionClass($contratoRelation);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);

        $clausulaReflection = new ReflectionClass($clausulaRelation);
        $clausulaTableProperty = $clausulaReflection->getProperty('table');
        $clausulaTableProperty->setAccessible(true);

        $contratoTable = $tableProperty->getValue($contratoRelation);
        $clausulaTable = $clausulaTableProperty->getValue($clausulaRelation);

        $this->assertSame($contratoTable, $clausulaTable);
        $this->assertSame('Clausula_Contrato', $contratoTable);
    }
}
