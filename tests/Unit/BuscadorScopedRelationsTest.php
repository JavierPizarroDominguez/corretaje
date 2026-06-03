<?php

namespace Tests\Unit;

use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Rendering\StubRenderer;
use App\Generator\Schema\TableSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the scoped relations fix in the CRUD generator.
 *
 * These tests verify the behavior of ColumnMetadata, StubRenderer methods
 * without requiring a database connection — they work on in-memory objects.
 */
class BuscadorScopedRelationsTest extends TestCase
{
    private string $stubsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stubsPath = realpath(__DIR__ . '/../../stubs');
    }

    // ================================================================
    // Task 1.1: ColumnMetadata scopedTargetFk property
    // ================================================================

    public function test_column_metadata_has_scoped_target_fk_property(): void
    {
        $col = $this->makeScopedColumn();

        $this->assertSame('Cliente_id', $col->scopedTargetFk);
    }

    public function test_column_metadata_scoped_target_fk_defaults_to_null(): void
    {
        $col = new ColumnMetadata(
            table: 'contrato',
            name: 'Ciudad_id',
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
        );

        $this->assertNull($col->scopedTargetFk);
    }

    // ================================================================
    // Task 2.1 / 4.2: buildScopedColumn sets correct values
    // ================================================================

    public function test_scoped_column_uses_resolved_target_table(): void
    {
        $col = $this->makeScopedColumn();

        // referencedTable should be the TARGET table (cliente), not pivot
        $this->assertSame('cliente', $col->referencedTable);
        // relatedModelName should be the TARGET model (Cliente)
        $this->assertSame('Cliente', $col->relatedModelName);
        // relatedModelVariable should be the TARGET variable (cliente)
        $this->assertSame('cliente', $col->relatedModelVariable);
        // scopedTargetFk should be the FK pointing to target
        $this->assertSame('Cliente_id', $col->scopedTargetFk);
        // pivotModel should still be set (for pivot record creation)
        $this->assertSame('App\Models\ParticipanteCobro', $col->pivotModel);
        // pivotFk should be the parent FK
        $this->assertSame('Cobro_id', $col->pivotFk);
    }

    public function test_scoped_column_relation_names_are_correct(): void
    {
        $deudor = $this->makeScopedColumn('deudor', 'Deudor');

        $this->assertSame('deudor', $deudor->relationName);
        $this->assertSame('buscador', $deudor->relationInputType);

        $acreedor = $this->makeScopedColumn('acreedor', 'Acreedor');

        $this->assertSame('acreedor', $acreedor->relationName);
        $this->assertSame('buscador', $acreedor->relationInputType);
    }

    // ================================================================
    // Task 2.2 / 4.3: buildCreateBuscadorCalls emits tipo='cliente'
    // ================================================================

    public function test_create_buscador_call_uses_target_table_for_scoped_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildCreateBuscadorCalls', [$schema]);

        // The tipo parameter should be 'cliente' (target table), not 'participante_cobro'
        $this->assertStringContainsString("tipo:  'cliente'", $output);
    }

    public function test_create_buscador_call_on_select_sets_hidden_input_for_scoped(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildCreateBuscadorCalls', [$schema]);

        // onSelect should set the hidden input with item.id
        $this->assertStringContainsString(
            "document.getElementById('input-create-deudor-id').value = item.id",
            $output
        );
    }

    public function test_create_buscador_call_sets_hidden_input_for_all_buscador_fields(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithNonScopedBuscador();

        $output = $this->invokePrivate($renderer, 'buildCreateBuscadorCalls', [$schema]);

        // All buscador fields should now set the hidden input with item.id
        $this->assertStringContainsString(
            "document.getElementById('input-create-ciudad-id').value = item.id",
            $output
        );
        // Should still set the display input
        $this->assertStringContainsString(
            "document.getElementById('input-create-ciudad').value = item.texto",
            $output
        );
        // tipo should be 'ciudad' (the referenced table)
        $this->assertStringContainsString("tipo:  'ciudad'", $output);
    }

    // ================================================================
    // Task 2.2 (cont): Hidden input in createFormFields
    // ================================================================

    public function test_create_form_fields_adds_hidden_input_for_scoped(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();
        $simpleFragment = '            <div class="mb-3">'
            . "\n" . '                <label for="{{field}}" class="form-label">{{label}}</label>'
            . "\n" . '                <input type="{{input_type}}" class="form-control" id="{{field}}" name="{{field}}" {{model}}>'
            . "\n" . '            </div>';

        $fields = $this->invokePrivate($renderer, 'buildCreateFormFields', [$schema, $simpleFragment]);

        $fieldsStr = implode("\n", $fields);

        // Should contain the hidden input for the scoped target FK
        $this->assertStringContainsString('name="deudor_Cliente_id"', $fieldsStr);
        $this->assertStringContainsString('id="input-create-deudor-id"', $fieldsStr);
        $this->assertStringContainsString('type="hidden"', $fieldsStr);
    }

    public function test_build_create_form_fields_replaces_scoped_fk_name_placeholder(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();
        // Fragment uses {{scoped_fk_name}} which should be replaced with deudor_Cliente_id
        $fragment = '<select name="{{scoped_fk_name}}" class="form-select"></select>';

        $fields = $this->invokePrivate($renderer, 'buildCreateFormFields', [$schema, $fragment]);
        $fieldsStr = implode("\n", $fields);

        // The placeholder must be replaced, not left as literal {{scoped_fk_name}}
        $this->assertStringNotContainsString('{{scoped_fk_name}}', $fieldsStr);
        $this->assertStringContainsString('name="deudor_Cliente_id"', $fieldsStr);
    }

    // ================================================================
    // Task 2.3 / 4.4: buildPivotStoreFields uses buscadorInputName
    // and correct FKs
    // ================================================================

    public function test_pivot_store_fields_uses_hidden_fk_for_scoped_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Should check the hidden FK input (deudor_Cliente_id)
        $this->assertStringContainsString("if (!empty(\$data['deudor_Cliente_id']))", $output);

        // Should use findOrFail on the target model (Cliente)
        $this->assertStringContainsString(
            '\App\Models\Cliente::findOrFail($data[\'deudor_Cliente_id\'])',
            $output
        );

        // Should use pivotFk for parent FK (Cobro_id)
        $this->assertStringContainsString(
            '$pivotParticipanteCobro->Cobro_id = $cobro->id',
            $output
        );

        // Should use scopedTargetFk for target FK (Cliente_id)
        $this->assertStringContainsString(
            '$pivotParticipanteCobro->Cliente_id = $relatedCliente->id',
            $output
        );

        // Should set the scope column
        $this->assertStringContainsString(
            "\$pivotParticipanteCobro->rol = 'Deudor'",
            $output
        );
    }

    public function test_pivot_store_fields_empty_for_non_scoped_schema(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithNonScopedBuscador();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        $this->assertSame('', $output);
    }

    public function test_pivot_store_fields_uses_find_or_fail_only(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Should use findOrFail
        $this->assertStringContainsString('findOrFail', $output);
        // Should NOT contain firstOrCreate (text-only fallback is removed)
        $this->assertStringNotContainsString('firstOrCreate', $output);
        // Should NOT contain the elseif branch with display name
        $this->assertStringNotContainsString("\$data['nombre-deudor']", $output);
    }

    // ================================================================
    // Task 2.4: buildStoreFieldLine skips special_relation
    // ================================================================

    public function test_store_field_line_returns_null_for_special_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();
        $col = $this->makeScopedColumn();

        $result = $this->invokePrivate($renderer, 'buildStoreFieldLine', [
            $col,
            $schema,
            'fragmentSimple',
            'fragmentBuscador',
            'fragmentFkSelect',
        ]);

        $this->assertNull($result);
    }

    // ================================================================
    // Task 2.5: buildUpdateFields skips special_relation
    // ================================================================

    public function test_update_fields_skips_special_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildUpdateFields', [$schema]);

        // The output should be empty since the only column is special_relation
        $this->assertSame('', trim($output));
    }

    // ================================================================
    // Task 2.6: buildValidationRules adds hidden FK rule
    // ================================================================

    public function test_validation_rules_uses_required_with_for_hidden_fk(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildValidationRules', [$schema]);

        // Should have rule for the hidden FK input with required_with
        $this->assertStringContainsString("deudor_Cliente_id", $output);
        $this->assertStringContainsString("required_with:nombre-deudor", $output);
        $this->assertStringContainsString("exists:cliente,id", $output);

        // Should have rule for the display input (nombre-deudor)
        $this->assertStringContainsString("nombre-deudor", $output);
    }

    public function test_validation_rules_scoped_relation_has_single_fk_rule(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildValidationRules', [$schema]);

        // Should have exactly ONE rule for the hidden FK (deudor_Cliente_id), not two
        // Count occurrences of 'deudor_Cliente_id' in the output
        $count = substr_count($output, 'deudor_Cliente_id');
        $this->assertSame(1, $count, 'Should have exactly one rule for deudor_Cliente_id');
        // Should NOT have a separate rule for 'deudor' (the relation name)
        $this->assertStringNotContainsString("'deudor'", $output);
    }

    public function test_validation_rules_normal_buscador_uses_required_with(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithNonScopedBuscador();

        $output = $this->invokePrivate($renderer, 'buildValidationRules', [$schema]);

        // Non-scoped buscador should use required_with for the hidden FK
        $this->assertStringContainsString("Ciudad_id", $output);
        $this->assertStringContainsString("required_with:nombre-ciudad", $output);
        $this->assertStringContainsString("exists:ciudad,id", $output);
    }

    // ================================================================
    // Task 1.2 / 4.1: RelationResolver resolve pivot belongsTo
    // ================================================================

    public function test_resolver_detects_participante_cobro_belongs_to(): void
    {
        $resolver = new RelationResolver();
        $relations = $resolver->resolve('App\Models\ParticipanteCobro');

        $cobroRel = $this->findRelationByName($relations, 'cobro');
        $this->assertNotNull($cobroRel);
        $this->assertSame('belongsTo', $cobroRel->type);
        $this->assertSame('Cobro_id', $cobroRel->foreignKey);

        $clienteRel = $this->findRelationByName($relations, 'cliente');
        $this->assertNotNull($clienteRel);
        $this->assertSame('belongsTo', $clienteRel->type);
        $this->assertSame('Cliente_id', $clienteRel->foreignKey);
    }

    public function test_resolver_detects_cobro_scoped_relations(): void
    {
        $resolver = new RelationResolver();
        $scoped = $resolver->getScopedRelations('App\Models\Cobro');

        // Debe detectar al menos deudor y acreedor
        $this->assertArrayHasKey('deudor', $scoped);
        $this->assertArrayHasKey('acreedor', $scoped);

        $deudor = $scoped['deudor'];
        $this->assertSame('hasOne-scoped', $deudor['type']);
        $this->assertSame('rol', $deudor['scopeColumn']);
        $this->assertSame('Deudor', $deudor['scopeValue']);

        // Si isPivotTable es true (DB disponible), verificar los nuevos campos
        if ($deudor['isPivotTable']) {
            $this->assertSame('Cobro_id', $deudor['parentFk']);
            $this->assertSame('Cliente_id', $deudor['targetFk']);
            $this->assertSame('App\Models\Cliente', $deudor['targetModel']);
            $this->assertSame('cliente', $deudor['targetTable']);

            // foreignKey debe usar el FK explícito, no getForeignKey()
            $this->assertNotSame('participante_cobro_id', $deudor['foreignKey']);
            $this->assertSame('Cobro_id', $deudor['foreignKey']);
        } else {
            // Si no hay DB, los campos relacionados deben ser null
            $this->assertNull($deudor['parentFk']);
            $this->assertNull($deudor['targetFk']);
            $this->assertNull($deudor['targetModel']);
            $this->assertNull($deudor['targetTable']);
        }
    }

    // ================================================================
    // Task: buildPivotUpdateFields (Fix 1 — update path for scoped pivot)
    // ================================================================

    public function test_pivot_update_fields_uses_first_or_new_and_find_or_fail(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        // Should use firstOrNew to find or create existing pivot
        $this->assertStringContainsString('firstOrNew', $output);

        // Should use findOrFail to resolve target
        $this->assertStringContainsString('findOrFail', $output);

        // Should NOT use firstOrCreate (text-only fallback is removed)
        $this->assertStringNotContainsString('firstOrCreate', $output);

        // Should match on parent FK + scope column/value
        $this->assertStringContainsString("'Cobro_id' => \$cobro->id", $output);
        $this->assertStringContainsString("'rol' => 'Deudor'", $output);

        // Should update the target FK
        $this->assertStringContainsString(
            '$pivotParticipanteCobro->Cliente_id = $relatedCliente->id',
            $output
        );

        // Should handle extra fields (monto)
        $this->assertStringContainsString('monto', $output);

        // Should save the pivot record
        $this->assertStringContainsString('$pivotParticipanteCobro->save()', $output);
    }

    public function test_pivot_update_fields_empty_for_non_scoped_schema(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithNonScopedBuscador();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        $this->assertSame('', $output);
    }

    public function test_pivot_update_fields_checks_hidden_fk_and_uses_find_or_fail(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        // Should guard with empty check on the hidden FK
        $this->assertStringContainsString("if (!empty(\$data['deudor_Cliente_id']))", $output);

        // Should use findOrFail on target model
        $this->assertStringContainsString(
            '\App\Models\Cliente::findOrFail($data[\'deudor_Cliente_id\'])',
            $output
        );

        // Should NOT contain firstOrCreate (text-only fallback removed)
        $this->assertStringNotContainsString('firstOrCreate', $output);
    }

    // ================================================================
    // Fix 2: Both deudor AND acreedor in same schema
    // ================================================================

    public function test_both_deudor_and_acreedor_have_distinct_buscador_calls(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithBothScopedRelations();

        $output = $this->invokePrivate($renderer, 'buildCreateBuscadorCalls', [$schema]);

        // Distinct input names
        $this->assertStringContainsString("input-create-deudor", $output);
        $this->assertStringContainsString("input-create-acreedor", $output);

        // Both set item.id on their own hidden input (no collision)
        $this->assertStringContainsString("input-create-deudor-id').value = item.id", $output);
        $this->assertStringContainsString("input-create-acreedor-id').value = item.id", $output);

        // Both use tipo 'cliente'
        $this->assertStringContainsString("tipo:  'cliente'", $output);
    }

    public function test_both_deudor_and_acreedor_have_distinct_pivot_store_fields(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithBothScopedRelations();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        // Distinct hidden FK names
        $this->assertStringContainsString("deudor_Cliente_id", $output);
        $this->assertStringContainsString("acreedor_Cliente_id", $output);

        // Different scope values
        $this->assertStringContainsString("\$pivotParticipanteCobro->rol = 'Deudor'", $output);
        $this->assertStringContainsString("\$pivotParticipanteCobro->rol = 'Acreedor'", $output);
    }

    public function test_both_deudor_and_acreedor_have_distinct_hidden_inputs_in_form(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithBothScopedRelations();
        $simpleFragment = '            <div class="mb-3">'
            . "\n" . '                <label for="{{field}}" class="form-label">{{label}}</label>'
            . "\n" . '                <input type="{{input_type}}" class="form-control" id="{{field}}" name="{{field}}" {{model}}>'
            . "\n" . '            </div>';

        $fields = $this->invokePrivate($renderer, 'buildCreateFormFields', [$schema, $simpleFragment]);
        $fieldsStr = implode("\n", $fields);

        // Both hidden inputs exist
        $this->assertStringContainsString('name="deudor_Cliente_id"', $fieldsStr);
        $this->assertStringContainsString('name="acreedor_Cliente_id"', $fieldsStr);
        $this->assertStringContainsString('id="input-create-deudor-id"', $fieldsStr);
        $this->assertStringContainsString('id="input-create-acreedor-id"', $fieldsStr);
        $this->assertStringContainsString('type="hidden"', $fieldsStr);
    }

    public function test_both_deudor_and_acreedor_have_distinct_pivot_update_fields(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithBothScopedRelations();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        // Both should have update code
        $this->assertStringContainsString('Actualizar deudor', $output);
        $this->assertStringContainsString('Actualizar acreedor', $output);

        // Both should use firstOrNew with distinct hidden FKs
        $this->assertStringContainsString('deudor_Cliente_id', $output);
        $this->assertStringContainsString('acreedor_Cliente_id', $output);

        // Different scope values
        $this->assertStringContainsString("'rol' => 'Deudor'", $output);
        $this->assertStringContainsString("'rol' => 'Acreedor'", $output);
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function makeRenderer(): StubRenderer
    {
        // Crear instancia sin llamar al constructor para evitar base_path()
        $refClass = new \ReflectionClass(StubRenderer::class);
        $renderer = $refClass->newInstanceWithoutConstructor();
        // Set stubs path via reflection
        $refProp = $refClass->getProperty('stubsPath');
        $refProp->setAccessible(true);
        $refProp->setValue($renderer, $this->stubsPath);
        return $renderer;
    }

    private function makeScopedColumn(string $relationName = 'deudor', string $scopeValue = 'Deudor'): ColumnMetadata
    {
        return new ColumnMetadata(
            table: 'cobro',
            name: $relationName,
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
            relationName: $relationName,
            relationDisplayField: 'nombre',
            relationInputType: 'buscador',
            relationInputName: 'nombre',
            isEditable: true,
            isCalculated: false,
            label: ucfirst($relationName),
            pivotModel: 'App\Models\ParticipanteCobro',
            pivotFk: 'Cobro_id',
            scopeColumn: 'rol',
            scopeValue: $scopeValue,
            pivotExtraFields: '["monto"]',
            scopedTargetFk: 'Cliente_id',
        );
    }

    private function makeSchemaWithScopedRelation(): TableSchema
    {
        $schema = new TableSchema();
        $schema->table = 'cobro';
        $schema->modelClass = 'App\Models\Cobro';
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
        $schema->columns = [$this->makeScopedColumn()];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        return $schema;
    }

    private function makeSchemaWithNonScopedBuscador(): TableSchema
    {
        $ciudadCol = new ColumnMetadata(
            table: 'contrato',
            name: 'Ciudad_id',
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
        );

        $schema = new TableSchema();
        $schema->table = 'contrato';
        $schema->modelClass = 'App\Models\Contrato';
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
        $schema->columns = [$ciudadCol];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        return $schema;
    }

    private function makeSchemaWithBothScopedRelations(): TableSchema
    {
        $deudor  = $this->makeScopedColumn('deudor', 'Deudor');
        $acreedor = $this->makeScopedColumn('acreedor', 'Acreedor');

        $schema = new TableSchema();
        $schema->table = 'cobro';
        $schema->modelClass = 'App\Models\Cobro';
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
        $schema->columns = [$deudor, $acreedor];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        return $schema;
    }

    private function findRelationByName(array $relations, string $name): ?object
    {
        foreach ($relations as $rel) {
            if ($rel->name === $name) {
                return $rel;
            }
        }
        return null;
    }

    private function invokePrivate(object $object, string $methodName, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }
}
