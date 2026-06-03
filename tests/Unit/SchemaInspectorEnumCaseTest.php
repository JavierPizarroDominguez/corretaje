<?php

namespace Tests\Unit;

use App\Generator\Introspection\SchemaInspector;
use Tests\TestCase;

/**
 * Tests for SchemaInspector ENUM case preservation.
 *
 * Verifies:
 * - ENUM values retain original database casing
 * - Boolean TINYINT(1) detection is case-insensitive
 * - parseEnumValues handles uppercase ENUM prefix
 */
class SchemaInspectorEnumCaseTest extends TestCase
{
    /**
     * Use reflection to access private parseEnumValues method.
     */
    private function invokeParseEnumValues(SchemaInspector $inspector, string $columnType): array
    {
        $reflector = new \ReflectionClass($inspector);
        $method = $reflector->getMethod('parseEnumValues');
        $method->setAccessible(true);
        return $method->invoke($inspector, $columnType);
    }

    /**
     * Use reflection to access private buildColumnMetadata method.
     */
    private function invokeBuildColumnMetadata(SchemaInspector $inspector, object $row): \App\Generator\Introspection\ColumnMetadata
    {
        $reflector = new \ReflectionClass($inspector);
        $method = $reflector->getMethod('buildColumnMetadata');
        $method->setAccessible(true);
        return $method->invoke($inspector, $row, 'test_table', []);
    }

    // ──────────────────────────────────────────────────────────────────
    // Phase 2.1: ENUM values retain original casing
    // ──────────────────────────────────────────────────────────────────

    public function test_parse_enum_values_preserves_lowercase_values(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "enum('Ingreso','Renta','Arrendador')");

        $this->assertSame(['Ingreso', 'Renta', 'Arrendador'], $result);
    }

    public function test_parse_enum_values_preserves_uppercase_values(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "enum('ACTIVO','INACTIVO','PENDIENTE')");

        $this->assertSame(['ACTIVO', 'INACTIVO', 'PENDIENTE'], $result);
    }

    public function test_parse_enum_values_preserves_mixed_case_values(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "enum('PDF','pdf','Pdf')");

        $this->assertSame(['PDF', 'pdf', 'Pdf'], $result);
    }

    // ──────────────────────────────────────────────────────────────────
    // Phase 2.2: ENUM prefix check is case-insensitive
    // ──────────────────────────────────────────────────────────────────

    public function test_parse_enum_values_handles_uppercase_enum_prefix(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "ENUM('Activo','Inactivo')");

        $this->assertSame(['Activo', 'Inactivo'], $result);
    }

    public function test_parse_enum_values_handles_mixed_case_enum_prefix(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "Enum('Valor1','Valor2')");

        $this->assertSame(['Valor1', 'Valor2'], $result);
    }

    public function test_parse_enum_values_returns_empty_for_non_enum(): void
    {
        $inspector = new SchemaInspector();
        $result = $this->invokeParseEnumValues($inspector, "varchar(255)");

        $this->assertSame([], $result);
    }

    // ──────────────────────────────────────────────────────────────────
    // Phase 2.3–2.5: Boolean detection is case-insensitive
    // ──────────────────────────────────────────────────────────────────

    public function test_build_column_metadata_tinyint_1_is_boolean_lowercase(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'activo',
            'DATA_TYPE' => 'tinyint',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => 'tinyint(1)',
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertTrue($metadata->isBoolean, 'tinyint(1) with lowercase COLUMN_TYPE should be detected as boolean');
        $this->assertStringContainsString('tinyint', $metadata->sqlType);
    }

    public function test_build_column_metadata_tinyint_1_is_boolean_uppercase(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'activo',
            'DATA_TYPE' => 'tinyint',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => 'TINYINT(1)',
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertTrue($metadata->isBoolean, 'TINYINT(1) with uppercase COLUMN_TYPE should be detected as boolean');
    }

    public function test_build_column_metadata_tinyint_4_is_not_boolean(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'ordinal',
            'DATA_TYPE' => 'tinyint',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => 'tinyint(4)',
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertFalse($metadata->isBoolean, 'tinyint(4) should NOT be detected as boolean');
    }

    public function test_build_column_metadata_tinyint_1_uppercase_is_not_boolean(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'ordinal',
            'DATA_TYPE' => 'tinyint',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => 'TINYINT(4)',
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertFalse($metadata->isBoolean, 'TINYINT(4) uppercase should NOT be detected as boolean');
    }

    // ──────────────────────────────────────────────────────────────────
    // Phase 2.6: ENUM enumValues in metadata preserve original casing
    // ──────────────────────────────────────────────────────────────────

    public function test_build_column_metadata_enum_values_preserve_casing(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'estado_civil',
            'DATA_TYPE' => 'enum',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => "enum('Soltero','Casado','Divorciado')",
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertSame(['Soltero', 'Casado', 'Divorciado'], $metadata->enumValues);
    }

    public function test_build_column_metadata_enum_uppercase_prefix_preserves_values(): void
    {
        $inspector = new SchemaInspector();
        $row = (object) [
            'COLUMN_NAME' => 'tipo_estado',
            'DATA_TYPE' => 'enum',
            'CHARACTER_MAXIMUM_LENGTH' => null,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => null,
            'COLUMN_TYPE' => "ENUM('Activo','Inactivo')",
            'COLUMN_KEY' => '',
            'EXTRA' => '',
            'REFERENCED_TABLE_NAME' => null,
            'REFERENCED_COLUMN_NAME' => null,
        ];

        $metadata = $this->invokeBuildColumnMetadata($inspector, $row);

        $this->assertSame(['Activo', 'Inactivo'], $metadata->enumValues);
    }
}