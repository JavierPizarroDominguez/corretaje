<?php

namespace Tests\Unit;

use App\Generator\Rendering\StubRenderer;
use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Schema\TableSchema;
use Tests\TestCase;

/**
 * Tests for generator-pivot-name-lookup change:
 * - Phase 1: Namespace fix (leading backslash)
 * - Phase 2: Validation + name resolution
 * - Phase 3: Stub select name + hidden inputs
 */
class GeneratorPivotNameLookupTest extends TestCase
{
    // ================================================================
    // Phase 1: Namespace Fix — leading backslash in generated code
    // ================================================================

    public function test_build_pivot_store_fields_uses_leading_backslash_for_pivot_model(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Must use leading backslash for absolute namespace to prevent ClassNotFoundError
        // The controller lives in App\Http\Controllers\Crud and without \ PHP resolves relative
        $this->assertStringContainsString(
            '\\App\\Models\\ParticipanteCobro',
            $output,
            'Pivot model instantiation must use leading backslash for absolute namespace'
        );
    }

    public function test_build_pivot_update_fields_uses_leading_backslash_for_pivot_model(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        // Must use leading backslash for firstOrNew
        $this->assertStringContainsString(
            '\\App\\Models\\ParticipanteCobro::firstOrNew',
            $output,
            'Pivot firstOrNew must use leading backslash for absolute namespace'
        );
    }

    public function test_build_pivot_store_fields_uses_leading_backslash_for_target_model(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Target model (Cliente) must also use absolute namespace
        $this->assertStringContainsString(
            '\\App\\Models\\Cliente::findOrFail',
            $output
        );
        $this->assertStringNotContainsString(
            '\\App\\Models\\Cliente::firstOrCreate',
            $output
        );
    }

    public function test_build_pivot_update_fields_uses_leading_backslash_for_target_model(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        $this->assertStringContainsString(
            '\\App\\Models\\Cliente::findOrFail',
            $output
        );
        $this->assertStringNotContainsString(
            '\\App\\Models\\Cliente::firstOrCreate',
            $output
        );
    }

    // ================================================================
    // Phase 2: Validation rules — sometimes|nullable for scoped FK
    // ================================================================

    public function test_build_validation_rules_uses_sometimes_nullable_for_scoped_fk(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildValidationRules', [$schema]);

        // Strict buscador: scoped FK uses required_with:text_input to enforce selection
        $this->assertStringContainsString(
            'required_with:nombre-deudor|integer|exists:cliente,id',
            $output,
            'Scoped FK validation must require text input to enforce buscador selection'
        );
        // Must NOT contain nullable (strict mode: either both fields or neither)
        $this->assertStringNotContainsString(
            "deudor_Cliente_id' => 'sometimes|nullable",
            $output,
            'Scoped FK must not be nullable in strict mode'
        );
    }

    public function test_build_validation_rules_adds_buscador_text_rule(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildValidationRules', [$schema]);

        // Buscador text input must have validation rule for firstOrCreate fallback
        $this->assertStringContainsString(
            'sometimes|nullable|string',
            $output,
            'Buscador text input must have sometimes|nullable|string rule'
        );
    }

    // ================================================================
    // Phase 2: Name resolution — firstOrCreate fallback in store/update
    // ================================================================

    public function test_build_pivot_store_fields_has_firstorcreate_fallback(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Strict buscador: no firstOrCreate fallback; only findOrFail with hidden FK
        $this->assertStringNotContainsString('firstOrCreate', $output);
        $this->assertStringContainsString('findOrFail', $output);
        $this->assertStringContainsString("\$data['deudor_Cliente_id']", $output);
    }

    public function test_build_pivot_update_fields_has_firstorcreate_fallback(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        $this->assertStringNotContainsString('firstOrCreate', $output);
        $this->assertStringContainsString('findOrFail', $output);
    }

    public function test_build_pivot_store_fields_uses_strict_buscador_without_firstorcreate(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Strict buscador: only hidden FK, no buscador text fallback
        $this->assertStringContainsString("\$data['deudor_Cliente_id']", $output);
        $this->assertStringNotContainsString('nombre-deudor', $output);
        $this->assertStringNotContainsString('firstOrCreate', $output);
    }

    public function test_build_pivot_update_fields_uses_strict_buscador_without_firstorcreate(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        $this->assertStringContainsString("\$data['deudor_Cliente_id']", $output);
        $this->assertStringNotContainsString('nombre-deudor', $output);
        $this->assertStringNotContainsString('firstOrCreate', $output);
    }

    // ================================================================
    // Phase 3: Stub — select name uses scoped FK key for scoped relations
    // ================================================================

    public function test_build_edit_form_fields_uses_scoped_fk_name_for_select(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();
        $simpleFragment = $this->loadStubFragment('edit-field.stub');

        $output = $this->invokePrivate($renderer, 'buildEditFormFields', [$schema, $simpleFragment]);
        $outputStr = implode('', $output);

        // For scoped relations, select name must be {relationName}_{scopedTargetFk}
        // NOT the plain fk_column (which would be ambiguous across scoped relations)
        $this->assertStringContainsString(
            'name="deudor_Cliente_id"',
            $outputStr,
            'Scoped relation select must use name="deudor_Cliente_id", not name="Cliente_id"'
        );
    }

    public function test_build_edit_form_fields_regular_fk_unchanged(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithRegularFkColumn();
        $simpleFragment = $this->loadStubFragment('edit-field.stub');

        $output = $this->invokePrivate($renderer, 'buildEditFormFields', [$schema, $simpleFragment]);
        $outputStr = implode('', $output);

        // Regular FK should use fk_column as name
        $this->assertStringContainsString('name="ciudad_id"', $outputStr);
    }

    // ================================================================
    // Phase 3: Inline edit — hidden FK input for scoped relations
    // ================================================================

    public function test_render_relation_fk_row_adds_hidden_fk_for_scoped_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();
        $col = $schema->columns[0];

        $output = $this->invokePrivate($renderer, 'renderRelationFkRow', [$schema, $col, 'Deudor']);

        // Hidden FK input must be present for scoped relations
        // so that onSelect can populate it with item.id before submit
        $this->assertStringContainsString(
            'type="hidden"',
            $output,
            'Scoped relation inline edit must include hidden FK input'
        );
        $this->assertStringContainsString(
            'name="deudor_Cliente_id"',
            $output,
            'Hidden FK must use scoped key name deudor_Cliente_id'
        );
    }

    public function test_render_relation_fk_row_regular_fk_no_hidden_input(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithRegularFkColumn();
        $col = $schema->regularForeignKeys()[0];

        $output = $this->invokePrivate($renderer, 'renderRelationFkRow', [$schema, $col, 'Ciudad']);

        // Regular FK should NOT have hidden FK input (not a scoped relation)
        $this->assertStringNotContainsString('type="hidden"', $output);
    }

    // ================================================================
    // Regression: Non-scoped FK relations unchanged
    // ================================================================

    public function test_build_pivot_store_fields_no_firstorcreate_for_regular_fk(): void
    {
        $renderer = $this->makeRenderer();
        // Schema with regular FK, no pivotModel/scopedTargetFk
        $schema = $this->makeSchemaWithRegularFkColumn();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Regular FK schemas have no scoped relations, so output should be empty
        $this->assertSame('', $output);
    }

    public function test_build_edit_form_fields_regular_fk_uses_fk_column_not_scoped_key(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithRegularFkColumn();
        $simpleFragment = $this->loadStubFragment('edit-field.stub');

        $output = $this->invokePrivate($renderer, 'buildEditFormFields', [$schema, $simpleFragment]);
        $outputStr = implode('', $output);

        // Regular FK should use fk_column (ciudad_id), not scoped key
        $this->assertStringContainsString('name="ciudad_id"', $outputStr);
        $this->assertStringNotContainsString('name="ciudad_Ciudad_id"', $outputStr);
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function makeRenderer(): StubRenderer
    {
        $stubsPath = realpath(__DIR__ . '/../../stubs');
        $refClass = new \ReflectionClass(StubRenderer::class);
        $renderer = $refClass->newInstanceWithoutConstructor();
        $refProp = $refClass->getProperty('stubsPath');
        $refProp->setAccessible(true);
        $refProp->setValue($renderer, $stubsPath);
        return $renderer;
    }

    private function makeSchemaWithScopedRelation(): TableSchema
    {
        $col = new ColumnMetadata(
            table: 'cobro',
            name: 'deudor',
            sqlType: 'special_relation',
            nullable: true,
            isPrimaryKey: false,
            isUnique: false,
            maxLength: null,
            isBoolean: false,
            enumValues: [],
            htmlInputType: 'text',
            isForeignKey: true,
            referencedTable: 'cliente',
            referencedColumn: null,
            relatedModelName: 'Cliente',
            relatedModelVariable: 'cliente',
            relationName: 'deudor',
            relationDisplayField: 'nombre',
            relationInputType: 'buscador',
            relationInputName: 'nombre',
            isEditable: true,
            isCalculated: false,
            label: 'Deudor',
            pivotModel: 'App\\Models\\ParticipanteCobro',
            pivotFk: 'Cobro_id',
            scopeColumn: 'rol',
            scopeValue: 'Deudor',
            pivotExtraFields: '["monto"]',
            scopedTargetFk: 'Cliente_id',
        );

        $schema = new TableSchema();
        $schema->table = 'cobro';
        $schema->modelClass = 'App\\Models\\Cobro';
        $schema->modelName = 'Cobro';
        $schema->modelVariable = 'cobro';
        $schema->modelPlural = 'cobros';
        $schema->modelSnake = 'cobro';
        $schema->modelTitle = 'cobro';
        $schema->routeBase = 'cobro';
        $schema->primaryKey = 'id';
        $schema->primaryKeys = ['id'];
        $schema->isCompositePk = false;
        $schema->isPivotTable = false;
        $schema->columns = [$col];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        return $schema;
    }

    private function makeSchemaWithRegularFkColumn(): TableSchema
    {
        $col = new ColumnMetadata(
            table: 'contrato',
            name: 'ciudad_id',
            sqlType: 'int',
            nullable: true,
            isPrimaryKey: false,
            isUnique: false,
            maxLength: null,
            isBoolean: false,
            enumValues: [],
            htmlInputType: 'text',
            isForeignKey: true,
            referencedTable: 'ciudad',
            referencedColumn: 'id',
            relatedModelName: 'Ciudad',
            relatedModelVariable: 'ciudad',
            relationName: 'ciudad',
            relationDisplayField: 'nombre',
            relationInputType: 'buscador',
            relationInputName: 'nombre',
            isEditable: true,
            isCalculated: false,
            label: 'Ciudad',
            pivotModel: null,
            pivotFk: null,
            scopeColumn: null,
            scopeValue: null,
            pivotExtraFields: null,
            scopedTargetFk: null,
        );

        $schema = new TableSchema();
        $schema->table = 'contrato';
        $schema->modelClass = 'App\\Models\\Contrato';
        $schema->modelName = 'Contrato';
        $schema->modelVariable = 'contrato';
        $schema->modelPlural = 'contratos';
        $schema->modelSnake = 'contrato';
        $schema->modelTitle = 'contrato';
        $schema->routeBase = 'contrato';
        $schema->primaryKey = 'id';
        $schema->primaryKeys = ['id'];
        $schema->isCompositePk = false;
        $schema->isPivotTable = false;
        $schema->columns = [$col];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        return $schema;
    }

    private function loadStubFragment(string $name): string
    {
        $path = realpath(__DIR__ . '/../../stubs/fragments/' . $name);
        if ($path === false) {
            throw new \ValueError("Stub fragment not found: {$name}");
        }
        return file_get_contents($path);
    }

    private function invokePrivate(object $object, string $methodName, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }
}