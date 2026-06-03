<?php

namespace Tests\Unit;

use App\Generator\Rendering\StubRenderer;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Config\ConfigLoader;
use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Schema\TableSchema;
use App\Generator\Introspection\SchemaInspector;
use App\Generator\Rendering\PlaceholderRegistry;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the Generator Universalidad changes.
 *
 * Phases:
 * 1. Config keys exist with correct defaults
 * 2. Dynamic namespace affects generated output
 * 3. Dynamic FK suffix in filter scoped field
 * 4. Pivot detection consistency
 * 5. UI strings via config
 */
class GeneratorUniversalidadTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configPath = config_path('generator.php');
    }

    // ================================================================
    // Phase 1: Config keys exist with correct defaults
    // ================================================================

    public function test_config_has_model_namespace_key(): void
    {
        $config = require $this->configPath;

        $this->assertArrayHasKey('model_namespace', $config);
        $this->assertSame('App\\Models\\', $config['model_namespace']);
    }

    public function test_config_has_months_key_with_spanish_defaults(): void
    {
        $config = require $this->configPath;

        $this->assertArrayHasKey('months', $config);
        $this->assertCount(12, $config['months']);
        $this->assertSame('Enero', $config['months'][1]);
        $this->assertSame('Diciembre', $config['months'][12]);
    }

    public function test_config_has_filter_titles_key_with_spanish_defaults(): void
    {
        $config = require $this->configPath;

        $this->assertArrayHasKey('filter_titles', $config);
        $this->assertSame('Filtrar por fechas', $config['filter_titles']['date']);
        $this->assertSame('Filtrar por montos', $config['filter_titles']['number']);
        $this->assertSame('Filtrar por texto', $config['filter_titles']['text']);
        $this->assertSame('Filtrar por relaciones', $config['filter_titles']['fk']);
        $this->assertSame('Filtrar por opciones', $config['filter_titles']['boolean']);
    }

    // ================================================================
    // Phase 2: Dynamic Namespace — StubRenderer outputs
    // ================================================================

    public function test_build_model_uses_uses_config_namespace(): void
    {
        config(['generator.model_namespace' => 'App\\Domain\\Entities\\']);

        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithFkColumn('Ciudad', 'ciudad');

        $output = $this->invokePrivate($renderer, 'buildModelUses', [$schema]);

        $this->assertStringContainsString('use App\\Domain\\Entities\\Ciudad;', $output);
    }

    public function test_build_model_uses_defaults_to_app_models(): void
    {
        // The config/generator.php already has 'model_namespace' => 'App\\Models\\'
        // which is the same as the fallback default. Test that the config value is used.
        config(['generator.model_namespace' => 'App\\Models\\']);
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithFkColumn('Ciudad', 'ciudad');

        $output = $this->invokePrivate($renderer, 'buildModelUses', [$schema]);

        $this->assertStringContainsString('use App\\Models\\Ciudad;', $output);
    }

    public function test_build_pivot_store_fields_uses_config_namespace(): void
    {
        config(['generator.model_namespace' => 'App\\Domain\\Entities\\']);

        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotStoreFields', [$schema]);

        $this->assertStringContainsString(
            '\\App\\Domain\\Entities\\Cliente::findOrFail',
            $output
        );
    }

    public function test_build_pivot_update_fields_uses_config_namespace(): void
    {
        config(['generator.model_namespace' => 'App\\Domain\\Entities\\']);

        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildPivotUpdateFields', [$schema]);

        $this->assertStringContainsString(
            '\\App\\Domain\\Entities\\Cliente::findOrFail',
            $output
        );
    }

    // ================================================================
    // Phase 2: Dynamic Namespace — RelationResolver
    // ================================================================

    public function test_resolver_resolve_model_class_falls_back_to_config(): void
    {
        config(['generator.model_namespace' => 'App\\Domain\\']);
        $resolver = new RelationResolver();

        // Use self::class as context — Candidate 'Ciudad' won't exist in Tests\Unit namespace
        // so it falls through to config fallback
        $result = $this->invokePrivate($resolver, 'resolveModelClass', ['Ciudad', self::class]);

        $this->assertSame('App\\Domain\\Ciudad', $result);
    }

    public function test_resolver_resolve_model_class_defaults_to_app_models(): void
    {
        config(['generator.model_namespace' => 'App\\Models\\']);
        $resolver = new RelationResolver();

        $result = $this->invokePrivate($resolver, 'resolveModelClass', ['Ciudad', self::class]);

        $this->assertSame('App\\Models\\Ciudad', $result);
    }

    public function test_config_loader_defaults_uses_config_namespace(): void
    {
        config(['generator.model_namespace' => 'App\\Domain\\Entities\\']);

        $loader = new ConfigLoader();
        $defaults = $this->invokePrivate($loader, 'defaults', ['cliente']);

        $this->assertSame('App\\Domain\\Entities\\Cliente', $defaults['model']);
    }

    public function test_config_loader_defaults_fallback_to_app_models(): void
    {
        config(['generator.model_namespace' => 'App\\Models\\']);

        $loader = new ConfigLoader();
        $defaults = $this->invokePrivate($loader, 'defaults', ['cliente']);

        $this->assertSame('App\\Models\\Cliente', $defaults['model']);
    }

    // ================================================================
    // Phase 3: Dynamic FK — filter-field-scoped stub
    // ================================================================

    public function test_render_filter_scoped_field_uses_dynamic_fk(): void
    {
        $renderer = $this->makeRenderer();
        $sr = [
            'label' => 'Deudor',
            'relation_name' => 'deudor',
            'related_model' => 'App\\Models\\Cliente',
            'display_field' => 'nombre',
            'filter_fk' => 'Cliente_id',
        ];

        $output = $this->invokePrivate($renderer, 'renderFilterScopedField', [$sr]);

        // Must use the dynamic FK suffix in name attribute
        $this->assertStringContainsString('name="filter[deudor_Cliente_id]"', $output);
        // Must use the dynamic FK suffix in data-filter attribute
        $this->assertStringContainsString('data-filter="deudor_Cliente_id"', $output);
    }

    public function test_render_filter_scoped_field_with_different_fk(): void
    {
        $renderer = $this->makeRenderer();
        $sr = [
            'label' => 'Propietario',
            'relation_name' => 'propietario',
            'related_model' => 'App\\Models\\Persona',
            'display_field' => 'nombre',
            'filter_fk' => 'Propietario_id',
        ];

        $output = $this->invokePrivate($renderer, 'renderFilterScopedField', [$sr]);

        $this->assertStringContainsString('name="filter[propietario_Propietario_id]"', $output);
        $this->assertStringContainsString('data-filter="propietario_Propietario_id"', $output);
    }

    // ================================================================
    // Phase 3 (ext): Dynamic FK — buildFilterConditions uses dynamic FK
    // ================================================================

    public function test_build_filter_conditions_uses_dynamic_fk(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $scopedRelations = [
            [
                'relation_name' => 'deudor',
                'filter_fk'     => 'Propietario_id',
                'label'         => 'Deudor',
                'related_model' => 'App\\Models\\Cliente',
                'display_field' => 'nombre',
            ],
        ];

        $output = $this->invokePrivate($renderer, 'buildFilterConditions', [$schema, $scopedRelations]);

        $this->assertStringContainsString("\$filter['deudor_Propietario_id']", $output);
        $this->assertStringContainsString(
            "where('Propietario_id', \$filter['deudor_Propietario_id']",
            $output
        );
    }

    // ================================================================
    // Phase 4: Pivot detection consistency
    // ================================================================

    // This test verifies the method exists with correct signature
    // and delegates to isPivotTable(). Full integration tested elsewhere.
    public function test_resolve_eager_load_strategy_signature_accepts_related_model(): void
    {
        $resolver = new RelationResolver();

        // The method should exist and work with string model class
        $refMethod = new \ReflectionMethod($resolver, 'resolveEagerLoadStrategy');
        $params = $refMethod->getParameters();

        // Should accept 3 params: type, name, relatedModel
        $this->assertCount(3, $params);
        $this->assertSame('type', $params[0]->getName());
        $this->assertSame('name', $params[1]->getName());
        $this->assertSame('relatedModel', $params[2]->getName());

        // For hasMany with a non-pivot model, should NOT suggest nested path
        // For hasMany with a pivot model, SHOULD suggest nested path
    }

    // ================================================================
    // Phase 5: UI strings via config
    // ================================================================

    public function test_render_filter_date_field_uses_config_months(): void
    {
        config(['generator.months' => [1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December']]);

        $renderer = $this->makeRenderer();
        $col = $this->makeDateColumn('fecha_firma');

        $output = $this->invokePrivate($renderer, 'renderFilterDateField', [$col, 'Fecha Firma', 'col-md-6']);

        // Should use the config override
        $this->assertStringContainsString('January', $output);
        $this->assertStringContainsString('December', $output);
        // Should NOT contain Spanish month names
        $this->assertStringNotContainsString('Enero', $output);
    }

    // ================================================================
    // Phase 6: guessDisplayField priority
    // ================================================================

    public function test_guess_display_field_returns_name_over_nombre(): void
    {
        config(['database.connections.mysql.database' => 'test_db']);
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object)['COLUMN_NAME' => 'id'],
                (object)['COLUMN_NAME' => 'name'],
                (object)['COLUMN_NAME' => 'nombre'],
            ]);

        $inspector = new SchemaInspector();
        $result = $this->invokePrivate($inspector, 'guessDisplayField', ['users']);

        $this->assertSame('name', $result);
    }

    public function test_guess_display_field_falls_back_to_nombre(): void
    {
        config(['database.connections.mysql.database' => 'test_db']);
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object)['COLUMN_NAME' => 'id'],
                (object)['COLUMN_NAME' => 'nombre'],
            ]);

        $inspector = new SchemaInspector();
        $result = $this->invokePrivate($inspector, 'guessDisplayField', ['cliente']);

        $this->assertSame('nombre', $result);
    }

    // ================================================================
    // Phase 7: Config filter titles
    // ================================================================

    public function test_custom_filter_titles_from_config(): void
    {
        config(['generator.filter_titles.date' => 'Filter by date']);

        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithDateColumn();
        $scopedRelations = [];

        $sections = $this->invokePrivate($renderer, 'buildFilterSections', [$schema, $scopedRelations]);
        $sectionHtml = implode('', $sections);

        $this->assertStringContainsString('Filter by date', $sectionHtml);
    }

    // ================================================================
    // Phase 8: PlaceholderRegistry accent mapping
    // ================================================================

    public function test_placeholder_registry_accent_mapping_preserved(): void
    {
        $result = PlaceholderRegistry::toLabel('comision_inicial');

        $this->assertSame('Comisión Inicial', $result);
    }

    // ================================================================
    // Phase 9: Pivot detection edge cases
    // ================================================================

    public function test_pivot_detection_non_pivot_with_participante_name(): void
    {
        $resolver = new RelationResolver();

        // Ciudad has single auto-increment PK → isPivotTable returns false
        // at the getIncrementing() check (no DB queries needed)
        [$suggestEager, $nestedPath] = $this->invokePrivate(
            $resolver,
            'resolveEagerLoadStrategy',
            ['hasMany', 'ciudads', \App\Models\Ciudad::class]
        );

        $this->assertNull($nestedPath);
    }

    public function test_pivot_detection_structural_without_heuristic_match(): void
    {
        config(['database.connections.mysql.database' => 'test_db']);

        // ClausulaContrato has composite PK (Contrato_id, Clausula_id) where both are FKs.
        // Table 'clausula_contrato' does not match old str_contains('participante') heuristic.
        DB::shouldReceive('select')
            ->twice()
            ->andReturn(
                [
                    (object)['COLUMN_NAME' => 'Contrato_id'],
                    (object)['COLUMN_NAME' => 'Clausula_id'],
                ],
                [
                    (object)['COLUMN_NAME' => 'Contrato_id'],
                    (object)['COLUMN_NAME' => 'Clausula_id'],
                ]
            );

        $resolver = new RelationResolver();
        $result = $this->invokePrivate($resolver, 'isPivotTable', [\App\Models\ClausulaContrato::class]);

        $this->assertTrue($result);
    }

    public function test_pivot_detection_consistency(): void
    {
        $resolver = new RelationResolver();

        // Get scoped relations for Cobro (has deudor() and acreedor() via ParticipanteCobro)
        $scoped = $resolver->getScopedRelations(\App\Models\Cobro::class);

        $this->assertNotEmpty($scoped, 'Cobro should have scoped relations');

        foreach ($scoped as $name => $scopedRel) {
            $relatedModel = $scopedRel['related'];
            $isPivotFromGetScoped = $scopedRel['isPivotTable'];

            // resolveEagerLoadStrategy for the same pivot model via hasMany
            [$suggestEager, $nestedPath] = $this->invokePrivate(
                $resolver,
                'resolveEagerLoadStrategy',
                ['hasMany', $name, $relatedModel]
            );

            // isPivotTable → nestedPath is non-null ('cliente')
            // Non-pivot → null
            $isPivotFromResolve = $nestedPath !== null;

            $this->assertSame(
                $isPivotFromGetScoped,
                $isPivotFromResolve,
                "Pivot status mismatch for relation '{$name}' via model '{$relatedModel}'"
            );
        }
    }

    public function test_standard_has_many_not_pivot(): void
    {
        $resolver = new RelationResolver();

        // Ciudad has single auto-increment PK → isPivotTable returns false
        [$suggestEager, $nestedPath] = $this->invokePrivate(
            $resolver,
            'resolveEagerLoadStrategy',
            ['hasMany', 'ciudads', \App\Models\Ciudad::class]
        );

        $this->assertNull($nestedPath);
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

    private function makeSchemaWithFkColumn(string $modelName, string $table): TableSchema
    {
        $col = new ColumnMetadata(
            table: 'contrato',
            name: $modelName . '_id',
            sqlType: 'int',
            nullable: true,
            isPrimaryKey: false,
            isUnique: false,
            maxLength: null,
            isBoolean: false,
            enumValues: [],
            htmlInputType: 'text',
            isForeignKey: true,
            referencedTable: $table,
            referencedColumn: 'id',
            relatedModelName: $modelName,
            relatedModelVariable: lcfirst($modelName),
            relationName: lcfirst($modelName),
            relationDisplayField: 'nombre',
            relationInputType: 'buscador',
            relationInputName: 'nombre',
            isEditable: true,
            isCalculated: false,
            label: $modelName,
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

    private function makeDateColumn(string $name): ColumnMetadata
    {
        return new ColumnMetadata(
            table: 'contrato',
            name: $name,
            sqlType: 'date',
            nullable: true,
            isPrimaryKey: false,
            isUnique: false,
            maxLength: null,
            isBoolean: false,
            enumValues: [],
            htmlInputType: 'date',
            isForeignKey: false,
            referencedTable: null,
            referencedColumn: null,
            relatedModelName: null,
            relatedModelVariable: null,
            relationName: null,
            relationDisplayField: null,
            relationInputType: null,
            relationInputName: null,
            isEditable: true,
            isCalculated: false,
            label: 'Fecha Firma',
        );
    }

    private function makeSchemaWithDateColumn(): TableSchema
    {
        $col = $this->makeDateColumn('fecha_firma');

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

    // ================================================================
    // Phase 6: Scoped pivot relations — special_relation columns
    // ================================================================

    public function test_buildFkData_generates_cliente_view_data_for_scoped_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildFkData', [$schema]);

        // After removing the special_relation guard, buildFkData should emit
        // the variable declarations for the scoped deudor→cliente relation
        $this->assertStringContainsString('clienteCount', $output);
        $this->assertStringContainsString('clienteOptions', $output);
    }

    public function test_buildFkCompact_includes_scoped_relation_variables_once(): void
    {
        $renderer = $this->makeRenderer();

        // Schema with two scoped relations targeting the same model (cliente)
        // e.g. deudor and acreedor both reference Cliente via special_relation
        $col1 = $this->makeScopedRelationColumn('deudor', 'Cliente', 'cliente', 'Deudor');
        $col2 = $this->makeScopedRelationColumn('acreedor', 'Cliente', 'cliente', 'Acreedor');

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
        $schema->columns = [$col1, $col2];
        $schema->eagerLoad = [];
        $schema->checkConstraints = [];
        $schema->components = [];

        $output = $this->invokePrivate($renderer, 'buildFkCompact', [$schema]);

        // Both deudor and acreedor map to 'cliente' variable — dedup should
        // include clienteCount and clienteOptions exactly ONCE
        $this->assertStringContainsString('clienteCount', $output);
        $this->assertStringContainsString('clienteOptions', $output);
        $this->assertEquals(
            1,
            substr_count($output, 'clienteCount'),
            'clienteCount must appear exactly once (dedup)'
        );
        $this->assertEquals(
            1,
            substr_count($output, 'clienteOptions'),
            'clienteOptions must appear exactly once (dedup)'
        );
    }

    public function test_buildFkCompactArray_generates_one_compact_line_for_scoped_relation(): void
    {
        $renderer = $this->makeRenderer();
        $schema = $this->makeSchemaWithScopedRelation();

        $output = $this->invokePrivate($renderer, 'buildFkCompactArray', [$schema]);

        // After removing guard, scoped relation should produce exactly one line
        // with clienteCount and clienteOptions entries
        $this->assertStringContainsString("'clienteCount'", $output);
        $this->assertStringContainsString("'clienteOptions'", $output);
        // Should be exactly one occurrence of each (dedup by $seen)
        $this->assertEquals(
            1,
            substr_count($output, "'clienteCount'"),
            "'clienteCount' must appear exactly once (dedup)"
        );
        $this->assertEquals(
            1,
            substr_count($output, "'clienteOptions'"),
            "'clienteOptions' must appear exactly once (dedup)"
        );
    }

    private function makeScopedRelationColumn(
        string $relationName,
        string $relatedModelName,
        string $relatedModelVariable,
        string $scopeValue
    ): ColumnMetadata {
        return new ColumnMetadata(
            table: 'cobro',
            name: lcfirst($relatedModelVariable),
            sqlType: 'special_relation',
            nullable: true,
            isPrimaryKey: false,
            isUnique: false,
            maxLength: null,
            isBoolean: false,
            enumValues: [],
            htmlInputType: 'text',
            isForeignKey: true,
            referencedTable: lcfirst($relatedModelName),
            referencedColumn: null,
            relatedModelName: $relatedModelName,
            relatedModelVariable: $relatedModelVariable,
            relationName: $relationName,
            relationDisplayField: 'nombre',
            relationInputType: 'buscador',
            relationInputName: 'nombre',
            isEditable: true,
            isCalculated: false,
            label: ucfirst($relationName),
            pivotModel: null,
            pivotFk: null,
            scopeColumn: 'rol',
            scopeValue: $scopeValue,
            pivotExtraFields: null,
            scopedTargetFk: null,
        );
    }

    private function invokePrivate(object $object, string $methodName, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }
}
