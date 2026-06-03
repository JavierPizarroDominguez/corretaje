<?php

namespace App\Generator\Schema;

use App\Generator\Config\ConfigLoader;
use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Introspection\ConstraintParser;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Introspection\SchemaInspector;
use Illuminate\Support\Str;

class SchemaBuilder
{
    public function __construct(
        private SchemaInspector  $inspector,
        private RelationResolver $relationResolver,
        private ConstraintParser $constraintParser,
        private ConfigLoader     $configLoader,
    ) {}

    public function build(string $table): TableSchema
    {
        $config = $this->configLoader->load($table);
        $schema = new TableSchema();

        // ── Nombres ──────────────────────────────────────────────────
        $schema->table         = $table;
        $schema->modelClass    = $config['model'];
        $schema->modelName     = class_basename($config['model']);
        $schema->modelVariable = Str::camel($schema->modelName);
        $schema->modelPlural   = Str::plural($schema->modelVariable);
        $schema->modelSnake    = Str::snake($schema->modelName);
        $schema->modelTitle    = strtolower($schema->modelName);
        $schema->routeBase     = $config['route_base'];

        // ── Primary Key ──────────────────────────────────────────────
        // Usamos lo que detecta el inspector directamente de la BD
        $schema->primaryKeys   = $this->inspector->getPrimaryKeys($table);

        // Si config define PKs explícitas (PK compuesta), usar esas
        if (!empty($config['pk'])) {
            $schema->primaryKeys = $config['pk'];
        }

        $schema->isCompositePk = count($schema->primaryKeys) > 1;
        $schema->primaryKey    = strtolower($schema->primaryKeys[0]);

        // ── Columnas ─────────────────────────────────────────────────
        // El SchemaInspector recibe la config de campos y relaciones
        // y construye el ColumnMetadata completo e inmutable de una vez.
        // NO intentamos mutar las columnas después.
        $fieldConfig = $this->buildFieldConfig($config, $table);
        $schema->columns = $this->inspector->getColumns($table, $fieldConfig);

        // ── Detectar si es tabla pivote ───────────────────────────────
        // Una tabla pivote tiene: PK compuesta + todas las PKs son FKs
        $fkColumns = [];
        foreach ($schema->columns as $col) {
            if ($col->isForeignKey) {
                $fkColumns[$col->name] = true;
            }
        }

        $schema->isPivotTable = $schema->isCompositePk
            && count($schema->primaryKeys) >= 2
            && empty(array_diff($schema->primaryKeys, array_keys($fkColumns)));

        // ── Relaciones especiales (arrendador, arrendatario, etc.) ───
        // hasOne con where() — no son FK directas en la tabla
        // Primero: desde config (manual)
        foreach ($config['special_relations'] ?? [] as $key => $cfg) {
            $schema->columns[] = $this->buildSpecialColumn($key, $cfg, $schema);
        }

        // Segundo: auto-detectar desde el modelo Eloquent (RelationResolver)
        // Solo para modelos no-pivote (los pivotes no tienen scoped relations)
        if (!$schema->isPivotTable && class_exists($config['model'])) {
            $scopedRelations = $this->relationResolver->getScopedRelations($config['model']);

            foreach ($scopedRelations as $key => $rel) {
                // Solo incluir si la relación apunta a una tabla pivote
                if ($rel['isPivotTable']) {
                    $schema->columns[] = $this->buildScopedColumn($key, $rel, $schema);
                }
            }
        }

        // ── Campos calculados declarados en config ────────────────────
        // Accessors Eloquent que queremos mostrar en la vista
        foreach ($config['fields'] ?? [] as $fieldName => $fieldCfg) {
            if (($fieldCfg['calculated'] ?? false) === true) {
                $label = $fieldCfg['label'] ?? $this->generateLabel($fieldName);
                $schema->columns[] = ColumnMetadata::calculated($table, $fieldName, $label);
            }
        }

        // ── Eager load ───────────────────────────────────────────────
        // Si config define eager_load, usar eso directamente.
        // Si no, sugerir automáticamente desde las relaciones Eloquent.
        if (!empty($config['eager_load'])) {
            $schema->eagerLoad = $config['eager_load'];
        } else {
            $schema->eagerLoad = $this->relationResolver->getEagerLoadSuggestions($config['model']);
        }

        // ── CHECK constraints ────────────────────────────────────────
        $rawConstraints    = $this->constraintParser->getCheckConstraints($table);
        $configConstraints = $config['constraints'] ?? [];

        // Constraints detectadas en BD — mensaje de config o fallback auto
        foreach ($rawConstraints as $name => $clause) {
            $schema->checkConstraints[$name] = $configConstraints[$name]
                ?? $this->constraintParser->autoMessage($name);
        }

        // Constraints solo definidas en config (MySQL < 8.0.16 o triggers)
        foreach ($configConstraints as $name => $message) {
            $schema->checkConstraints[$name] ??= $message;
        }

        // ── Components ───────────────────────────────────────────────
        $schema->components = $config['components'];

        return $schema;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Traduce la config de generator.php al formato que espera SchemaInspector::getColumns().
     *
     * generator.php maneja 'relations' y 'fields' por separado.
     * SchemaInspector espera un array plano por nombre de columna con claves:
     *   editable, calculated, label, related_model, relation_name,
     *   display_field, input_type, input_name, related_var
     */
    private function buildFieldConfig(array $config, string $table): array
    {
        $fieldConfig   = $config['fields'] ?? [];
        $displayFields = config('generator.display_fields', []);

        // Obtener las FK de la tabla para cruzarlas con display_fields global
        $tableFks = $this->inspector->getForeignKeys($table);

        foreach ($config['relations'] ?? [] as $fkColumn => $relCfg) {
            $referencedTable = $tableFks[$fkColumn]['table'] ?? null;
            $globalDisplay   = $referencedTable ? ($displayFields[$referencedTable] ?? null) : null;

            // El display_field en config puede ser un valor genérico guardado antes
            // de que el usuario respondiera la pregunta. Si el global es más específico, usarlo.
            $savedDisplay  = $relCfg['display_field'] ?? null;
            $isGeneric     = in_array($savedDisplay, [null, 'id', 'nombre', '']);
            $displayField  = (!$isGeneric)
                ? $savedDisplay          // config explícito no genérico: respetar
                : ($globalDisplay        // global más específico: usar
                    ?? $savedDisplay     // fallback al guardado
                    ?? 'id');           // último recurso

            $fieldConfig[$fkColumn] = array_merge(
                $fieldConfig[$fkColumn] ?? [],
                [
                    'relation_name' => $relCfg['relation_name'] ?? null,
                    'display_field' => $displayField,
                    'input_type'    => $relCfg['type']          ?? 'buscador',
                    'input_name'    => $displayField,
                    'related_route' => trim($relCfg['related_route'] ?? $referencedTable, '/'),
                ]
            );
        }

        // FK sin config en 'relations' pero con display_fields global
        foreach ($tableFks as $fkColumn => $fkInfo) {
            if (isset($fieldConfig[$fkColumn]['display_field'])
                && !in_array($fieldConfig[$fkColumn]['display_field'], ['id', 'nombre', ''])
            ) {
                continue; // ya tiene display_field no genérico
            }

            $globalDisplay = $displayFields[$fkInfo['table']] ?? null;
            if ($globalDisplay) {
                $fieldConfig[$fkColumn] = array_merge(
                    $fieldConfig[$fkColumn] ?? [],
                    [
                        'display_field' => $globalDisplay,
                        'input_name'    => $globalDisplay,
                    ]
                );
            }
        }

        return $fieldConfig;
    }

    /**
     * Construye un ColumnMetadata sintético para relaciones especiales.
     *
     * Soporta dos patrones:
     *
     * Patrón A — hasOne con where (patrón original):
     *   arrendador: hasOne(ParticipanteContrato)->where('rol', 'Arrendador')
     *   El update se hace vía ParticipanteContratoController (form_route/form_pk en config).
     *
     * Patrón C — belongsToMany con wherePivot (recomendado):
     *   arrendadores: belongsToMany(Cliente)->wherePivot('rol', 'Arrendador')
     *   El update se hace via sync() directamente en ContratoController.
     *   Se activa cuando config tiene 'pivot_column' y 'pivot_value'.
     */
    private function buildSpecialColumn(string $key, array $cfg, TableSchema $schema): ColumnMetadata
    {
        $isPivot = isset($cfg['pivot_column']) && isset($cfg['pivot_value']);

        return new ColumnMetadata(
            table:                $schema->table,
            name:                 $key,
            sqlType:              'special_relation',
            nullable:             true,
            isPrimaryKey:         false,
            isUnique:             false,
            maxLength:            null,
            isBoolean:            false,
            enumValues:           [],
            htmlInputType:        'text',
            isForeignKey:         true,
            referencedTable:      $cfg['related_route']  ?? null,
            referencedColumn:     $cfg['related_pk']     ?? null,
            relatedModelName:     class_basename($cfg['related_model']),
            relatedModelVariable: Str::camel(class_basename($cfg['related_model'])),
            relationName:         $cfg['relation_name'],
            relationDisplayField: $cfg['display_field'],
            relationInputType:    $cfg['type'],
            relationInputName:    $cfg['input_name'],
            isEditable:           true,
            isCalculated:         false,
            label:                ucfirst($key),
            isPivotRelation:      $isPivot,
            pivotColumn:          $cfg['pivot_column']  ?? null,
            pivotValue:           $cfg['pivot_value']   ?? null,
            relatedRoute:         $cfg['related_route'] ?? null,
            scopeColumn:          $cfg['scope_column'] ?? null,
            scopeValue:           $cfg['scope_value']  ?? null,
            pivotModel:           $cfg['pivot_model']   ?? null,
            pivotFk:              $cfg['pivot_fk']      ?? null,
            pivotExtraFields:     $cfg['pivot_extra_fields'] ?? null,
        );
    }

    /**
     * Construye un ColumnMetadata para relaciones scoped (hasOne + where)
     * detectadas automáticamente desde el modelo Eloquent.
     *
     * Ej: Cobro.deudor() -> hasOne(ParticipanteCobro)->where('rol', 'Deudor')
     */
    private function buildScopedColumn(string $key, array $rel, TableSchema $schema): ColumnMetadata
    {
        $relatedModel = $rel['related'];
        $relatedShort = class_basename($relatedModel);

        // Para relaciones scoped con tabla pivote, usar los datos resueltos
        // desde los belongsTo del pivote (target model = Cliente, no ParticipanteCobro)
        $referencedTable    = $rel['targetTable'] ?? $rel['relatedTable'];
        $targetModelClass   = $rel['targetModel'] ?? $relatedModel;
        $targetShort        = class_basename($targetModelClass);

        return new ColumnMetadata(
            table:                $schema->table,
            name:                 $key,
            sqlType:              'special_relation',
            nullable:             true,
            isPrimaryKey:         false,
            isUnique:             false,
            maxLength:            null,
            isBoolean:            false,
            enumValues:           [],
            htmlInputType:        'text',
            isForeignKey:         true,
            referencedTable:      $referencedTable,
            referencedColumn:    null, // La PK del modelo destino es 'id'
            relatedModelName:     $targetShort,
            relatedModelVariable: Str::camel($targetShort),
            relationName:         $key,
            relationDisplayField: 'nombre', // Default, el FkInterviewer preguntará
            relationInputType:    'buscador', // Default para scoped relations
            relationInputName:    'nombre', // Default
            isEditable:           true,
            isCalculated:         false,
            label:                ucfirst($key),
            isPivotRelation:      false,
            pivotColumn:          null,
            pivotValue:           null,
            relatedRoute:         $referencedTable,
            scopeColumn:          $rel['scopeColumn'],
            scopeValue:           $rel['scopeValue'],
            pivotModel:           $rel['related'],
            pivotFk:              $rel['foreignKey'],
            pivotExtraFields:     json_encode($rel['pivotExtraFields']),
            scopedTargetFk:       $rel['targetFk'] ?? null,
        );
    }

    private function generateLabel(string $fieldName): string
    {
        $name = preg_replace('/_id$/i', '', $fieldName);
        return ucwords(str_replace('_', ' ', strtolower($name)));
    }
}
