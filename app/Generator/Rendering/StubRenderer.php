<?php

namespace App\Generator\Rendering;

use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Schema\PrimaryKeyStrategy;
use App\Generator\Schema\TableSchema;
use Illuminate\Support\Str;

class StubRenderer
{
    private string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = base_path('stubs');
    }

    // ================================================================
    // CONTROLLER
    // ================================================================

    public function renderController(TableSchema $schema): string
    {
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);

        $stub = $this->loadStub('controller.stub');

        $stub = $this->replaceGlobal($stub, $schema);
        $pkParams = $pkStrategy->methodSignature();
        if ($pkStrategy->isComposite()) {
            $pkParts = [];
            foreach ($schema->primaryKeys as $pk) {
                $pkParts[] = "'" . $pk . "' => \$" . strtolower($pk);
            }
            $pkArray = '[' . implode(', ', $pkParts) . ']';
            $stub = str_replace('{{update_query}}', $schema->modelName . '::query()->where(' . $pkArray . ')->update($' . $schema->modelVariable . '->getDirty());', $stub);
        } else {
            $stub = str_replace('{{update_query}}', '$' . $schema->modelVariable . '->save();', $stub);
        }
        $stub = str_replace('{{validation_rules}}',   $this->buildValidationRules($schema),  $stub);
        $stub = str_replace('{{store_fields}}',       $this->buildStoreFields($schema),    $stub);
        $stub = str_replace('{{update_fields}}',      $this->buildUpdateFields($schema),    $stub);
        $stub = str_replace('{{eager_load}}',         $this->buildEagerLoad($schema),      $stub);
        $stub = str_replace('{{catch_constraints}}',  $this->buildCatchConstraints($schema), $stub);
        $stub = str_replace('{{fk_compact}}',         $this->buildFkCompact($schema),       $stub);
        $stub = str_replace('{{fk_compact_array}}',   $this->buildFkCompactArray($schema),  $stub);
        $stub = str_replace('{{model_uses}}',         $this->buildModelUses($schema),      $stub);
        $stub = str_replace('{{scoped_store_fields}}', $this->buildPivotStoreFields($schema), $stub);
        $stub = str_replace('{{scoped_update_fields}}', $this->buildPivotUpdateFields($schema), $stub);

        // {{fk_data}} aparece 3 veces (show, create, edit) — reemplazar todas
        $fkData = $this->buildFkData($schema);
        $stub   = str_replace('{{fk_data}}', $fkData, $stub);

        // Placeholders para PK simple vs compuesta
        if ($schema->isCompositePk) {
            $stub = str_replace('{{method_params}}',       $pkStrategy->methodSignature(),    $stub);
            $stub = str_replace('{{find_or_fail}}',        $pkStrategy->findOrFailExpression($schema->modelName),   $stub);
            $stub = str_replace('{{destroy_call}}',        $pkStrategy->destroyExpression($schema->modelName),      $stub);
            $stub = str_replace('{{redirect_params}}',      $pkStrategy->redirectParams($schema->modelVariable),    $stub);
            $stub = str_replace('{{pk_route_segments}}',   $pkStrategy->routeSegments(),    $stub);
            $stub = str_replace('{{pk_blade_segments}}',   $pkStrategy->bladeUrlSegments(), $stub);
            // Legacy support
            $stub = str_replace('{{pk}}',                  $schema->primaryKey, $stub);
        } else {
            $stub = str_replace('{{method_params}}',      '$' . $schema->primaryKey, $stub);
            $stub = str_replace('{{find_or_fail}}',       $schema->modelName . '::findOrFail($' . $schema->primaryKey . ')', $stub);
            $stub = str_replace('{{destroy_call}}',       $schema->modelName . '::destroy($' . $schema->primaryKey . ')', $stub);
            $stub = str_replace('{{redirect_params}}',     '$' . $schema->modelVariable . '->' . $schema->primaryKeys[0], $stub);
            $stub = str_replace('{{pk_blade_segments}}',   '{{ $' . $schema->modelVariable . '->' . $schema->primaryKeys[0] . ' }}', $stub);
            $stub = str_replace('{{pk}}',                  $schema->primaryKey, $stub);
        }

        return $stub;
    }

    // ================================================================
    // BUSCADOR CONTROLLER
    // Genera/actualiza BuscadorController con un bloque por cada modelo
    // que fue marcado como buscador en config/generator.php
    // ================================================================

    public function renderBuscadorController(array $searchableModels): string
    {
        $stub   = $this->loadStub('buscador-controller.stub');
        $uses   = [];
        $blocks = [];

        foreach ($searchableModels as $info) {
            $modelName  = class_basename($info['class']);
            $modelSnake = $info['model_snake'];
            $routeBase  = $info['route_base'];

            // Normalizar: search_paths puede ser ['nombre'] o [['path'=>'nombre','field'=>'nombre']]
            $rawPaths = $info['search_paths'] ?? [];
            $paths    = $this->normalizePaths($rawPaths);

            if (empty($paths)) {
                continue;
            }

            $uses[] = "use {$info['class']};";

            $conditions = $this->buildSearchConditions($paths);

            $block  = "        if (\$request->has('{$modelSnake}')) {\n";
            $ns = $this->getModelNamespace();
            $block .= "            \$resultados_{$modelSnake} = \\{$ns}{$modelName}::query()\n";
            $block .= $conditions;
            $block .= "                ->limit(10)\n";
            $block .= "                ->get();\n";
            $block .= "            foreach (\$resultados_{$modelSnake} as \$item) {\n";
            $block .= "                \$resultados[] = [\n";
            $block .= "                    'tipo'  => '{$modelSnake}',\n";
            $block .= "                    'texto' => \$this->getSearchText(\$item, " . json_encode($paths) . "),\n";
            $block .= "                    'url'   => '/{$routeBase}/' . \$item->id,\n";
            $block .= "                ];\n";
            $block .= "            }\n";
            $block .= "        }";

            $blocks[] = $block;
        }

        $stub = str_replace('{{buscador_blocks}}', implode("\n\n", $blocks),                    $stub);
        $stub = str_replace('{{model_uses}}',      implode("\n", array_unique($uses)) . "\n",   $stub);
        $stub = str_replace("\n}\n", "\n" . $this->buildGetSearchTextHelper() . "\n}\n", $stub);

        return $stub;
    }

    /**
     * Normaliza search_paths al formato ['nombre', 'propiedad.direccion'] independientemente
     * de si vienen como strings simples o como arrays con claves 'path'/'field'.
     */
    private function normalizePaths(array $rawPaths): array
    {
        $paths = [];
        foreach ($rawPaths as $item) {
            if (is_string($item)) {
                $paths[] = $item;
            } elseif (is_array($item) && isset($item['path'])) {
                $paths[] = $item['path'];
            } elseif (is_array($item) && isset($item[0]) && is_string($item[0])) {
                $paths[] = $item[0];
            }
        }
        return $paths;
    }

    private function buildSearchConditions(array $paths): string
    {
        if (empty($paths)) {
            return "                ->where('id', 'LIKE', \"%{\$q}%\")\n";
        }

        $lines = [];

        foreach ($paths as $i => $path) {
            $segments = explode('.', $path);
            $prefix   = $i === 0 ? '->' : '->or';

            if (count($segments) === 1) {
                $lines[] = "                {$prefix}where('{$segments[0]}', 'LIKE', \"%{\$q}%\")";
            } else {
                $field     = array_pop($segments);
                $relations = $segments;
                $hasMethod = $i === 0 ? 'whereHas' : 'orWhereHas';

                // Construir whereHas anidado de adentro hacia afuera
                $innermost = "fn(\$sub) => \$sub->where('{$field}', 'LIKE', \"%{\$q}%\")";
                for ($j = count($relations) - 1; $j >= 1; $j--) {
                    $innermost = "fn(\$sub) => \$sub->whereHas('{$relations[$j]}', {$innermost})";
                }

                $lines[] = "                ->{$hasMethod}('{$relations[0]}', {$innermost})";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function buildGetSearchTextHelper(): string
    {
        return <<<'PHPEOF'
    /**
     * Obtiene el texto descriptivo de un registro navegando el search_path.
     * Retorna el primer valor no vacío encontrado entre los paths.
     */
    private function getSearchText($item, array $paths): string
    {
        foreach ($paths as $path) {
            $segments = explode('.', $path);
            $value    = $item;
            foreach ($segments as $segment) {
                if (is_null($value)) break;
                $value = $value->$segment ?? null;
            }
            if (!empty($value) && is_scalar($value)) {
                return (string) $value;
            }
        }
        return (string) $item->id;
    }
PHPEOF;
    }

    public function renderRoutes(TableSchema $schema): string
    {
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);

        $stubFile = $schema->isCompositePk ? 'routes-composite.stub' : 'routes-simple.stub';
        $stub     = $this->loadStub($stubFile);

        $stub = $this->replaceGlobal($stub, $schema);
        $stub = str_replace('{{pk_route_segments}}', $pkStrategy->routeSegments(), $stub);
        $stub = str_replace('{{pk}}',                $schema->primaryKey,          $stub);

        return $stub;
    }

    // ================================================================
    // MODALES
    // ================================================================

    public function renderModalShow(TableSchema $schema): string
    {
        $stub = $this->loadStub('modal-show.stub');
        $rows = $this->buildFieldRows($schema, ['__all__']);
        $buscadorCalls = $this->buildBuscadorCalls($schema, ['__all__']);
        $stub = str_replace('{{field_rows}}', $rows, $stub);
        $stub = str_replace('{{buscador_calls}}', $buscadorCalls, $stub);
        $stub = $this->replaceGlobal($stub, $schema);
        return $stub;
    }

    public function renderModalCreate(TableSchema $schema): string
    {
        $stub          = $this->loadStub('modal-create.stub');
        $simpleFragment = $this->loadStub('fragments/create-field.stub');
        $fields        = $this->buildCreateFormFields($schema, $simpleFragment);
        $buscadorCalls = $this->buildCreateBuscadorCalls($schema);
        $stub = str_replace('{{create_fields}}',        implode("\n", $fields), $stub);
        $stub = str_replace('{{create_buscador_calls}}', $buscadorCalls,        $stub);
        $stub = $this->replaceGlobal($stub, $schema);
        return $stub;
    }

    // ================================================================
    // VISTA SHOW — Fix 7: contenido inline sin components separados
    // ================================================================

    public function renderShowView(TableSchema $schema): string
    {
        $stub = $this->loadStub('view-show.stub');

        // Reutilizar buildFieldRows y buildBuscadorCalls del component
        $allFieldNames = ['__all__'];
        $rows          = $this->buildFieldRows($schema, $allFieldNames);
        $buscadorCalls = $this->buildBuscadorCalls($schema, $allFieldNames);

        $stub = str_replace('{{field_rows}}',     $rows,          $stub);
        $stub = str_replace('{{buscador_calls}}', $buscadorCalls, $stub);
        $stub = $this->replaceGlobal($stub, $schema);

        return $stub;
    }

    // ================================================================
    // VISTA MAIN — mantenido por compatibilidad pero ya no se usa
    // ================================================================

    public function renderMainView(TableSchema $schema): string
    {
        $stub     = $this->loadStub('view-main.stub');
        $fragment = $this->loadStub('view-include.stub');

        $includes = [];
        foreach ($schema->components as $componentName => $fields) {
            $line = str_replace('{{component_name}}', $componentName,       $fragment);
            $line = str_replace('{{model}}',          $schema->modelVariable, $line);
            $includes[] = rtrim($line);
        }

        $stub = str_replace('{{component_includes}}', implode("\n", $includes), $stub);
        $stub = $this->replaceGlobal($stub, $schema);

        return $stub;
    }

    // ================================================================
    // VISTA INDEX
    // ================================================================

    public function renderIndexView(TableSchema $schema): string
    {
        $stub           = $this->loadStub('view-index.stub');
        $headerFragment = $this->loadStub('fragments/index-header.stub');
        $cellFragment   = $this->loadStub('fragments/index-cell.stub');
        $cellFkFragment = $this->loadStub('fragments/index-cell-fk.stub');

        $headers = [];
        $cells   = [];

        // fillableColumns ya incluye PK-FK para tablas pivote
        $columnsToShow = $schema->fillableColumns();

        foreach ($columnsToShow as $col) {
            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

            if ($col->isForeignKey && $col->relationName) {
                $headers[] = rtrim(str_replace('{{label}}', $label, $headerFragment));

                $displayField   = $col->relationDisplayField ?? 'id';
                $bladeAccess    = $this->displayFieldToBladeAccess($displayField);

                $line = str_replace('{{model}}',         $schema->modelVariable,                              $cellFkFragment);
                $line = str_replace('{{fk_column}}',     $col->name,                                          $line);
                $line = str_replace('{{relation}}',      $col->relationName,                                  $line);
                $line = str_replace('{{display_field}}', $bladeAccess,                                        $line);
                $line = str_replace('{{related_route}}', trim($col->relatedRoute ?? $col->referencedTable ?? '', '/'), $line);
                $cells[] = rtrim($line);
                continue;
            }

            if ($col->isForeignKey) {
                continue;
            }

            $headers[] = rtrim(str_replace('{{label}}', $label, $headerFragment));
            $line = str_replace('{{model}}', $schema->modelVariable, $cellFragment);
            $line = str_replace('{{field}}', $col->name,             $line);
            $cells[] = rtrim($line);
        }

        $stub = str_replace('{{index_headers}}', implode("\n", $headers), $stub);
        $stub = str_replace('{{index_cells}}',   implode("\n", $cells),   $stub);

        // Omitir buscador del index si la tabla tiene __none__ en search_paths
        $searchPaths = config("generator.search_paths.{$schema->table}", []);
        $hasBuscador = !empty($searchPaths) && $searchPaths !== ['__none__'];

        if (!$hasBuscador) {
            // Eliminar el bloque del buscador del index
            $stub = preg_replace('/\{\{-- Buscador.*?--\}\}/s', '', $stub);
            $stub = preg_replace('/<div class="mb-3 position-relative">.*?<\/div>\s*<\/div>/s', '', $stub);
        }

        $stub = $this->replaceGlobal($stub, $schema);
        
        // Reemplazar pk_blade_segments para PK compuesta
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
        if ($schema->isCompositePk) {
            $stub = str_replace('{{pk_blade_segments}}', $pkStrategy->bladeUrlSegments(), $stub);
        } else {
            $stub = str_replace('{{pk_blade_segments}}', '{{ $' . $schema->modelVariable . '->' . $schema->primaryKeys[0] . ' }}', $stub);
        }

        return $stub;
    }

    // ================================================================
    // VISTA CREATE
    // ================================================================

    public function renderCreateView(TableSchema $schema): string
    {
        $stub          = $this->loadStub('view-create.stub');
        $simpleFragment  = $this->loadStub('fragments/create-field.stub');

        $fields          = $this->buildCreateFormFields($schema, $simpleFragment);
        $buscadorCalls   = $this->buildCreateBuscadorCalls($schema);

        $stub = str_replace('{{create_fields}}',        implode("\n", $fields), $stub);
        $stub = str_replace('{{create_buscador_calls}}', $buscadorCalls,        $stub);
        $stub = $this->replaceGlobal($stub, $schema);

        return $stub;
    }

    // ================================================================
    // VISTA EDIT
    // ================================================================

    public function renderEditView(TableSchema $schema): string
    {
        $stub     = $this->loadStub('view-edit.stub');
        $fragment = $this->loadStub('fragments/edit-field.stub');

        $fields = $this->buildEditFormFields($schema, $fragment);
        $buscadorCalls = $this->buildEditBuscadorCalls($schema);

        $stub = str_replace('{{edit_fields}}', implode("\n", $fields), $stub);
        $stub = str_replace('{{edit_buscador_calls}}', $buscadorCalls, $stub);
        $stub = $this->replaceGlobal($stub, $schema);

        return $stub;
    }

    private function buildEditBuscadorCalls(TableSchema $schema): string
    {
        $lines = [];

        foreach ($schema->fillableColumns() as $col) {
            if (!$col->isForeignKey || !$col->isEditable) {
                continue;
            }

            $fieldId      = $col->relationName ?? $col->name;
            $pascal       = PlaceholderRegistry::toPascal($fieldId);
            $relatedTable = $col->referencedTable ?? $col->relatedRoute ?? $fieldId;

            $call  = "    buscador({\n";
            $call .= "        input: '#input-create-{$fieldId}',\n";
            $call .= "        list:  '#listaCreate{$pascal}',\n";
            $call .= "        tipo:  '{$relatedTable}',\n";
            $call .= "        onSelect: function(item) {\n";
            $call .= "            document.getElementById('input-create-{$fieldId}').value = item.texto;\n";
            $call .= "        }\n";
            $call .= "    });";
            $lines[] = $call;
        }

        return implode("\n\n", $lines);
    }

    private function buildEditFormFields(TableSchema $schema, string $simpleFragment): array
    {
        $fields           = [];
        $buscadorFragment = $this->loadStub('fragments/create-field-fk-buscador.stub');
        $selectFragment   = $this->loadStub('fragments/create-field-fk-select.stub');
        $enumFragment     = $this->loadStub('fragments/create-field-enum.stub');
        $enumOptFragment  = $this->loadStub('fragments/create-field-enum-option.stub');
        $booleanFragment  = $this->loadStub('fragments/edit-field-boolean.stub');

        foreach ($schema->fillableColumns() as $col) {
            if (!$col->isEditable) {
                continue;
            }

            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

            // FK editable (buscador o select según runtime threshold)
            if ($col->isForeignKey && $col->relationInputType !== 'link') {
                $fieldId          = $col->relationName ?? $col->name;
                $pascal           = PlaceholderRegistry::toPascal($fieldId);
                $relatedVar       = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');
                $buscadorInputName = $this->buscadorInputName($col);
                $sqlField         = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
                // For scoped relations, select name must match hidden FK key
                $scopedFkName = ($col->pivotModel !== null && $col->scopedTargetFk !== null)
                    ? ($col->relationName . '_' . $col->scopedTargetFk)
                    : $col->name;

                $line = str_replace('{{label}}',                $label,              $selectFragment);
                $line = str_replace('{{field_id}}',             $fieldId,            $line);
                $line = str_replace('{{FieldIdPascal}}',        $pascal,             $line);
                $line = str_replace('{{fk_column}}',            $col->name,          $line);
                $line = str_replace('{{buscador_input_name}}',  $buscadorInputName,  $line);
                $line = str_replace('{{input_name}}',           $buscadorInputName,  $line); // legacy
                $line = str_replace('{{display_field}}',        $sqlField,           $line);
                $line = str_replace('{{related_var}}',          $relatedVar,         $line);
                $line = str_replace('{{scoped_fk_name}}',      $scopedFkName,       $line);
                $fields[] = rtrim($line);

                // Para relaciones scoped con tabla pivote, el hidden input se gestiona
                // dentro del stub create-field-fk-select.stub (en el branch buscador)
                // ya que el mismo stub se reutiliza para edit mode.

                continue;
            }

            if ($col->isForeignKey) {
                continue; // link: no editable
            }

            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

            // ENUM
            if ($col->sqlType === 'enum') {
                $options = [];
                foreach ($col->enumValues as $value) {
                    $opt = str_replace('{{field}}',      $col->name, $enumOptFragment);
                    $opt = str_replace('{{enum_value}}', $value,     $opt);
                    $opt = str_replace('{{enum_label}}', $value,     $opt);
                    $opt = str_replace('{{model}}',      $schema->modelVariable, $opt);
                    $options[] = rtrim($opt);
                }
                $line = str_replace('{{label}}',        $label,     $enumFragment);
                $line = str_replace('{{field}}',        $col->name, $line);
                $line = str_replace('{{enum_options}}', implode("\n", $options), $line);
                $line = str_replace('{{model}}',        $schema->modelVariable, $line);
                $fields[] = rtrim($line);
                continue;
            }

            // Boolean
            if ($col->isBoolean) {
                $line = str_replace('{{label}}', $label,     $booleanFragment);
                $line = str_replace('{{field}}', $col->name, $line);
                $line = str_replace('{{model}}', $schema->modelVariable, $line);
                $fields[] = rtrim($line);
                continue;
            }

            // Campo simple
            $line  = str_replace('{{label}}',      $label,                 $simpleFragment);
            $line  = str_replace('{{field}}',      $col->name,             $line);
            $line  = str_replace('{{input_type}}', $col->htmlInputType,    $line);
            $line  = str_replace('{{model}}',      $schema->modelVariable, $line);

            $fields[] = rtrim($line);
        }

        return $fields;
    }

    // Genera campos simples + FK para formulario create
    private function buildCreateFormFields(TableSchema $schema, string $simpleFragment): array
    {
        $fields           = [];
        $buscadorFragment = $this->loadStub('fragments/create-field-fk-buscador.stub');
        $selectFragment   = $this->loadStub('fragments/create-field-fk-select.stub');
        $enumFragment     = $this->loadStub('fragments/create-field-enum.stub');
        $enumOptFragment  = $this->loadStub('fragments/create-field-enum-option.stub');
        $booleanFragment  = $this->loadStub('fragments/create-field-boolean.stub');

        foreach ($schema->fillableColumns() as $col) {
            if (!$col->isEditable) {
                continue;
            }

            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

            // FK editable (buscador o select según runtime threshold)
            if ($col->isForeignKey && $col->relationInputType !== 'link') {
                $fieldId          = $col->relationName ?? $col->name;
                $pascal           = PlaceholderRegistry::toPascal($fieldId);
                $relatedVar       = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');
                $buscadorInputName = $this->buscadorInputName($col);
                $sqlField         = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
                // For scoped relations, select name must match hidden FK key
                $scopedFkName = ($col->pivotModel !== null && $col->scopedTargetFk !== null)
                    ? ($col->relationName . '_' . $col->scopedTargetFk)
                    : $col->name;

                $line = str_replace('{{label}}',                $label,              $selectFragment);
                $line = str_replace('{{field_id}}',             $fieldId,            $line);
                $line = str_replace('{{FieldIdPascal}}',        $pascal,             $line);
                $line = str_replace('{{fk_column}}',            $col->name,          $line);
                $line = str_replace('{{buscador_input_name}}',  $buscadorInputName,  $line);
                $line = str_replace('{{input_name}}',           $buscadorInputName,  $line); // legacy
                $line = str_replace('{{display_field}}',        $sqlField,           $line);
                $line = str_replace('{{related_var}}',          $relatedVar,         $line);
                $line = str_replace('{{scoped_fk_name}}',      $scopedFkName,       $line);
                $fields[] = rtrim($line);

                // Para relaciones scoped con tabla pivote, el hidden input se gestiona
                // dentro del stub create-field-fk-select.stub (en el branch buscador)
                // ya que el mismo stub se reutiliza para edit mode.

                continue;
            }

            if ($col->isForeignKey) {
                continue; // link: no editable
            }

            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

            // ENUM
            if ($col->sqlType === 'enum') {
                $options = [];
                foreach ($col->enumValues as $value) {
                    $opt = str_replace('{{field}}',      $col->name, $enumOptFragment);
                    $opt = str_replace('{{enum_value}}', $value,     $opt);
                    $opt = str_replace('{{enum_label}}', $value,     $opt);
                    $opt = str_replace('{{model}}',      $schema->modelVariable, $opt);
                    $options[] = rtrim($opt);
                }
                $line = str_replace('{{label}}',        $label,     $enumFragment);
                $line = str_replace('{{field}}',        $col->name, $line);
                $line = str_replace('{{enum_options}}', implode("\n", $options), $line);
                $line = str_replace('{{model}}',        $schema->modelVariable, $line);
                $fields[] = rtrim($line);
                continue;
            }

            // Boolean
            if ($col->isBoolean) {
                $line = str_replace('{{label}}', $label,     $booleanFragment);
                $line = str_replace('{{field}}', $col->name, $line);
                $line = str_replace('{{model}}', $schema->modelVariable, $line);
                $fields[] = rtrim($line);
                continue;
            }

            // Campo simple
            $line  = str_replace('{{label}}',      $label,                 $simpleFragment);
            $line  = str_replace('{{field}}',      $col->name,             $line);
            $line  = str_replace('{{input_type}}', $col->htmlInputType,    $line);
            $line  = str_replace('{{model}}',      $schema->modelVariable, $line);

            $fields[] = rtrim($line);
        }

        return $fields;
    }

    // Genera las llamadas buscador() para la vista create
    private function buildCreateBuscadorCalls(TableSchema $schema): string
    {
        $lines = [];

        foreach ($schema->fillableColumns() as $col) {
            // Incluir todos los FK editables - el view decide buscador/select en runtime
            if (!$col->isForeignKey || !$col->isEditable) {
                continue;
            }

            $fieldId      = $col->relationName ?? $col->name;
            $pascal       = PlaceholderRegistry::toPascal($fieldId);
            $relatedTable = $col->referencedTable ?? $col->relatedRoute ?? $fieldId;

            $call  = "    buscador({\n";
            $call .= "        input: '#input-create-{$fieldId}',\n";
            $call .= "        list:  '#listaCreate{$pascal}',\n";
            $call .= "        tipo:  '{$relatedTable}',\n";
            $call .= "        onSelect: function(item) {\n";
            $call .= "            document.getElementById('input-create-{$fieldId}').value = item.texto;\n";
            $call .= "            document.getElementById('input-create-{$fieldId}-id').value = item.id;\n";
            $call .= "        }\n";
            $call .= "    });";
            $lines[] = $call;
        }

        return implode("\n\n", $lines);
    }





    public function renderComponent(TableSchema $schema, string $componentName, array $fieldNames): string
    {
        $stub = $this->loadStub('component-table.stub');

        $rows          = $this->buildFieldRows($schema, $fieldNames);
        $buscadorCalls = $this->buildBuscadorCalls($schema, $fieldNames);

        $stub = str_replace('{{field_rows}}',     $rows,          $stub);
        $stub = str_replace('{{buscador_calls}}', $buscadorCalls, $stub);
        $stub = $this->replaceGlobal($stub, $schema);

        return $stub;
    }

    // ================================================================
    // FIELD ROWS — itera columnas y elige el stub correcto por tipo
    // ================================================================

    private function buildFieldRows(TableSchema $schema, array $fieldNames): string
    {
        $lines = [];

        foreach ($schema->columns as $col) {
            // Si se especificaron campos concretos, filtrar
            if ($fieldNames !== ['__all__'] && !in_array($col->name, $fieldNames)) {
                continue;
            }

            // PK nunca se muestra, excepto PK-FK en tablas pivote
            if ($col->isPrimaryKey && !($schema->isPivotTable && $col->isForeignKey)) {
                continue;
            }

            $lines[] = $this->renderFieldRow($schema, $col);
        }

        return implode("\n\n", $lines);
    }

    // Elige el stub de fila correcto para cada columna
    private function renderFieldRow(TableSchema $schema, ColumnMetadata $col): string
    {
        $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);

        // Campo calculado o no editable → readonly
        if ($col->isCalculated || !$col->isEditable) {
            return $this->renderReadonlyRow($schema, $col, $label);
        }

        // Relación editable (buscador o select según count en runtime)
        if ($col->isForeignKey && in_array($col->relationInputType, ['buscador', 'select'])) {
            return $this->renderRelationFkRow($schema, $col, $label);
        }

        // Relación especial con buscador fijo (arrendador, arrendatario)
        if ($col->isForeignKey && $col->sqlType === 'special_relation') {
            return $this->renderRelationBuscadorRow($schema, $col, $label);
        }

        // Relación solo link (readonly con enlace)
        if ($col->isForeignKey && $col->relationInputType === 'link') {
            return $this->renderRelationLinkRow($schema, $col, $label);
        }

        // Boolean
        if ($col->isBoolean) {
            return $this->renderBooleanRow($schema, $col, $label);
        }

        // Enum
        if ($col->sqlType === 'enum') {
            return $this->renderEnumRow($schema, $col, $label);
        }

        // Campo estándar editable
        return $this->renderSimpleRow($schema, $col, $label);
    }

    private function renderSimpleRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub = $this->loadStub('component-inline-field.stub');
        $stub = $this->replaceFieldTokens($stub, $schema, $col, $label);
        $stub = str_replace('{{input_type}}', $col->htmlInputType, $stub);
        return $stub;
    }

    private function renderBooleanRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub = $this->loadStub('component-inline-boolean.stub');
        $stub = $this->replaceFieldTokens($stub, $schema, $col, $label);
        return $stub;
    }

    private function renderEnumRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub        = $this->loadStub('component-inline-enum.stub');
        $optionStub  = $this->loadStub('component-inline-enum-option.stub');

        $options = [];
        foreach ($col->enumValues as $value) {
            $opt = str_replace('{{enum_value}}', $value,      $optionStub);
            $opt = str_replace('{{enum_label}}', $value,      $opt);
            $opt = str_replace('{{model}}',      $schema->modelVariable, $opt);
            $opt = str_replace('{{field}}',      $col->name,  $opt);
            $options[] = rtrim($opt);
        }

        $stub = $this->replaceFieldTokens($stub, $schema, $col, $label);
        $stub = str_replace('{{enum_options}}', implode("\n", $options), $stub);
        return $stub;
    }

    private function renderRelationBuscadorRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub      = $this->loadStub('component-inline-relation-buscador.stub');
        $fieldId   = $col->relationName ?? $col->name;
        $pascal    = PlaceholderRegistry::toPascal($fieldId);

        // Form action: para relaciones especiales viene del config (PK compuesta)
        // Para FK simples: /<ruta_relacionada>/<pk>
        $formAction = $this->resolveFormAction($schema, $col);

        $stub = str_replace('{{label}}',          $label,                        $stub);
        $stub = str_replace('{{model}}',           $schema->modelVariable,        $stub);
        $stub = str_replace('{{field_id}}',        $fieldId,                      $stub);
        $stub = str_replace('{{FieldIdPascal}}',   $pascal,                       $stub);
        $stub = str_replace('{{related_route}}',   trim($col->relatedRoute ?? $col->referencedTable ?? '', '/'),       $stub);
        $stub = str_replace('{{related_pk}}',      $col->referencedColumn ?? 'id', $stub);
        $stub = str_replace('{{relation}}',        $col->relationName ?? '',        $stub);
        $stub = str_replace('{{display_field}}',   $col->relationDisplayField ?? 'nombre', $stub);
        $stub = str_replace('{{input_name}}',      $col->relationInputName ?? 'nombre',    $stub);
        $stub = str_replace('{{form_action}}',     $formAction,                   $stub);

        return $stub;
    }

    private function renderRelationLinkRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub = $this->loadStub('component-inline-relation-link.stub');
        $fieldId = $col->relationName ?? $col->name;

        $stub = str_replace('{{label}}',         $label,                                   $stub);
        $stub = str_replace('{{model}}',          $schema->modelVariable,                   $stub);
        $stub = str_replace('{{field_id}}',       $fieldId,                                 $stub);
        $stub = str_replace('{{related_route}}',  trim($col->relatedRoute ?? $col->referencedTable ?? '', '/'),                  $stub);
        $stub = str_replace('{{fk_column}}',      $col->name,                               $stub);
        $stub = str_replace('{{relation}}',       $col->relationName ?? '',                  $stub);
        $stub = str_replace('{{display_field}}',  $col->relationDisplayField ?? 'id',        $stub);
        $stub = str_replace('{{RelatedModel}}',   $col->relatedModelName ?? '',              $stub);

        return $stub;
    }

    private function renderReadonlyRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub = $this->loadStub('component-readonly-field.stub');
        $stub = $this->replaceFieldTokens($stub, $schema, $col, $label);
        return $stub;
    }

    // ================================================================
    // BUSCADOR JS CALLS
    // ================================================================

    private function buildBuscadorCalls(TableSchema $schema, array $fieldNames): string
    {
        $fragment = $this->loadStub('buscador-call.stub');
        $lines    = [];

        foreach ($schema->columns as $col) {
            if ($fieldNames !== ['__all__'] && !in_array($col->name, $fieldNames)) {
                continue;
            }

            if (!$col->isForeignKey || $col->relationInputType === 'link') {
                continue;
            }

            $fieldId     = $col->relationName ?? $col->name;
            $pascal      = PlaceholderRegistry::toPascal($fieldId);
            $relatedTable = $col->referencedTable ?? $col->relatedRoute ?? $fieldId;
            $modelPrefix  = $schema->modelVariable;

            // Para claves compuestas, usar pk_blade_segments
            if ($schema->isPivotTable || count($schema->primaryKeys) > 1) {
                $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
                $pkExpr = $pkStrategy->bladeUrlSegments();
            } else {
                $pkExpr = '{{ $' . $schema->modelVariable . '->' . $schema->primaryKey . ' }}';
            }

            if (in_array($col->relationInputType, ['buscador', 'select'])) {
                $call  = "    buscador({\n";
                $call .= "        input: '#input-{$modelPrefix}-{$pkExpr}-{$fieldId}',\n";
                $call .= "        list:  '#lista-{$modelPrefix}-{$pkExpr}-{$pascal}',\n";
                $call .= "        tipo:  '{$relatedTable}',\n";
                $call .= "        onSelect: function(item) {\n";
                $call .= "            document.getElementById('input-{$modelPrefix}-{$pkExpr}-{$fieldId}').value = item.texto;\n";
                $call .= "            document.getElementById('input-{$modelPrefix}-{$pkExpr}-{$fieldId}').closest('form').submit();\n";
                $call .= "        }\n";
                $call .= "    });";
                $lines[] = $call;
            }
        }

        return implode("\n\n", $lines);
    }

    // ================================================================
    // CONTROLLER FRAGMENTS
    // ================================================================

    // ================================================================
    // MODEL USES — genera los 'use App\Models\X' para FK en el controller
    // ================================================================

    private function buildModelUses(TableSchema $schema): string
    {
        $uses = [];
        $seen = [];

        foreach ($schema->columns as $col) {
            if (!$col->isForeignKey) {
                continue;
            }

            $modelName = $col->relatedModelName;
            if (!$modelName || isset($seen[$modelName])) {
                continue;
            }
            $seen[$modelName] = true;

            // No duplicar el modelo principal que ya está en el stub
            if ($modelName === $schema->modelName) {
                continue;
            }

            $uses[] = "use " . $this->getModelNamespace() . "{$modelName};";
        }

        if (empty($uses)) {
            return '';
        }

        return implode("\n", $uses) . "\n";
    }

    private function buildValidationRules(TableSchema $schema): string
    {
        $fragment = $this->loadStub('fragments/validation-rule.stub');
        $lines    = [];

        foreach ($schema->fillableColumns() as $col) {
            if ($col->isForeignKey && $col->relationInputType === 'buscador') {
                // Buscador: valida el campo de texto namespaced (ej: nombre-nacionalidad)
                $buscadorName = $this->buscadorInputName($col);
                $sqlField     = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
                // El campo de texto es nullable|string (se convierte a FK en el controller)
                $rules = 'sometimes|nullable|string';
                $line  = str_replace('{{field}}', $buscadorName, $fragment);
                $line  = str_replace('{{rules}}', $rules,        $line);
                $lines[] = rtrim($line);

                // Para relaciones scoped (con tabla pivote), el FK name es relationName_scopedTargetFk
                // Para FK directas (contrato, servicio, etc.), el FK name es col->name
                if ($col->pivotModel !== null && $col->scopedTargetFk !== null) {
                    $hiddenFkName = $col->relationName . '_' . $col->scopedTargetFk;
                } else {
                    $hiddenFkName = $col->name;
                }
                $hiddenRules = 'required_with:' . $buscadorName . '|integer|exists:' . $col->referencedTable . ',id';
                $lineHidden = str_replace('{{field}}', $hiddenFkName, $fragment);
                $lineHidden = str_replace('{{rules}}', $hiddenRules,  $lineHidden);
                $lines[] = rtrim($lineHidden);

            } elseif ($col->isForeignKey && $col->relationInputType === 'select') {
                // Select: valida el fk_column (id del select)
                $rules = $this->resolveValidationRule($col, $schema->table);
                $line  = str_replace('{{field}}', $col->name, $fragment);
                $line  = str_replace('{{rules}}', $rules,     $line);
                $lines[] = rtrim($line);

                // También valida buscador_input_name para el buscador secundario ("Agregar X")
                $buscadorName = $this->buscadorInputName($col);
                $lineB = str_replace('{{field}}', $buscadorName,              $fragment);
                $lineB = str_replace('{{rules}}', 'sometimes|nullable|string', $lineB);
                $lines[] = rtrim($lineB);

            } else {
                // Campo simple
                $rules = $this->resolveValidationRule($col, $schema->table);
                $line  = str_replace('{{field}}', $col->name, $fragment);
                $line  = str_replace('{{rules}}', $rules,     $line);
                $lines[] = rtrim($line);
            }
        }

        return implode("\n", $lines);
    }

    private function resolveValidationRule(ColumnMetadata $col, string $table): string
    {
        $rules = [];

        // 'sometimes' permite que el campo no esté presente en el request
        // Necesario porque los campos se editan uno a uno desde el inline editing
        $rules[] = 'sometimes';
        $rules[] = $col->nullable ? 'nullable' : 'required';

        if ($col->isBoolean) {
            $rules[] = 'boolean';
        } else {
            switch ($col->sqlType) {
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $rules[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $rules[] = 'numeric';
                    break;
                case 'varchar':
                case 'char':
                    $rules[] = 'string';
                    if ($col->maxLength) {
                        $rules[] = "max:{$col->maxLength}";
                    }
                    break;
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $rules[] = 'string';
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $rules[] = 'date';
                    break;
                case 'enum':
                    $rules[] = 'in:' . implode(',', $col->enumValues);
                    break;
            }
        }

        if ($col->isForeignKey && $col->referencedTable && $col->referencedColumn) {
            $rules[] = "exists:{$col->referencedTable},{$col->referencedColumn}";
        }

        if ($col->isUnique) {
            $rules[] = "unique:{$table},{$col->name}";
        }

        return implode('|', $rules);
    }

    private function buildStoreFields(TableSchema $schema): string
    {
        $fragmentSimple    = $this->loadStub('fragments/store-field-simple.stub');
        $fragmentBuscador  = $this->loadStub('fragments/store-field-relation-buscador.stub');
        $fragmentFkSelect  = $this->loadStub('fragments/store-field-fk-select.stub');
        $lines             = [];

        // Campos fillable (ya incluye PK-FK para tablas pivote)
        foreach ($schema->fillableColumns() as $col) {
            $line = $this->buildStoreFieldLine($col, $schema, $fragmentSimple, $fragmentBuscador, $fragmentFkSelect);
            if ($line) {
                $lines[] = rtrim($line);
            }
        }

        return implode("\n", $lines);
    }

    private function buildStoreFieldLine(ColumnMetadata $col, TableSchema $schema, string $fragmentSimple, string $fragmentBuscador, string $fragmentFkSelect): ?string
    {
        // Las relaciones scoped (deudor, acreedor) se manejan en pivotStoreFields
        if ($col->sqlType === 'special_relation') {
            return null;
        }

        if ($col->isForeignKey && $col->relationInputType === 'buscador') {
            return $this->fillRelationFragment($fragmentBuscador, $schema, $col);
        } elseif ($col->isForeignKey && $col->relationInputType === 'select') {
            $lineSelect   = str_replace('{{model}}',     $schema->modelVariable, $fragmentFkSelect);
            $lineSelect   = str_replace('{{fk_column}}', $col->name,             $lineSelect);
            $lineBuscador = $this->fillRelationFragment($fragmentBuscador, $schema, $col);
            return $lineSelect . "\n" . $lineBuscador;
        } elseif ($col->isForeignKey && $col->relationInputType === 'link') {
            return null;
        } else {
            $line = str_replace('{{model}}', $schema->modelVariable, $fragmentSimple);
            return str_replace('{{field}}', $col->name, $line);
        }
    }

    /**
     * Genera código para crear registros pivote desde el store del modelo padre.
     * Se usa cuando el modelo padre tiene scoped relations (deudor, acreedor, etc.)
     * que apuntan a tablas pivote.
     */
    private function buildPivotStoreFields(TableSchema $schema): string
    {
        $scopedRelations = $schema->scopedRelations();

        if (empty($scopedRelations)) {
            return '';
        }

        $lines = [];

        foreach ($scopedRelations as $col) {
            // Solo si la relación apunta a una tabla pivote con target resuelto
            if (!$col->pivotModel || !$col->pivotFk || !$col->scopeColumn || !$col->scopeValue || !$col->scopedTargetFk) {
                continue;
            }

            $pivotModel = $col->pivotModel;
            $pivotModelShort = class_basename($pivotModel);
            $fkColumn = $col->pivotFk;                  // parent FK (ej: Cobro_id)
            $scopeCol = $col->scopeColumn;
            $scopeVal = $col->scopeValue;
            $relationName = $col->relationName;
            $targetFk = $col->scopedTargetFk;            // target FK (ej: Cliente_id)
            $targetModelShort = $col->relatedModelName;  // target model (ej: Cliente)
            $extraFields = $col->pivotExtraFields ? json_decode($col->pivotExtraFields) : [];

            // Nombre del hidden input que contiene el ID del modelo destino
            // Convención: {relationName}_{scopedTargetFk} (ej: deudor_Cliente_id)
            $hiddenFkName = "{$relationName}_{$targetFk}";

            // Generar código para crear el registro pivote usando findOrFail
            $code = "            // Crear {$relationName}\n";
            $code .= "            \$related{$targetModelShort} = null;\n";
            $code .= "            if (!empty(\$data['{$hiddenFkName}'])) {\n";
            $ns = $this->getModelNamespace();
            $code .= "                \$related{$targetModelShort} = \\{$ns}{$targetModelShort}::findOrFail(\$data['{$hiddenFkName}']);\n";
            $code .= "            }\n";
            $code .= "            if (!empty(\$related{$targetModelShort})) {\n";
            $code .= "                \$pivot{$pivotModelShort} = new \\{$ns}{$pivotModelShort}();\n";
            $code .= "                \$pivot{$pivotModelShort}->{$fkColumn} = \$" . $schema->modelVariable . "->id;\n";
            $code .= "                \$pivot{$pivotModelShort}->{$targetFk} = \$related{$targetModelShort}->id;\n";
            $code .= "                \$pivot{$pivotModelShort}->{$scopeCol} = '{$scopeVal}';\n";

            // Actualizar campos extra del pivote (monto, etc.)
            foreach ($extraFields as $extraField) {
                $extraInputName = "{$relationName}_{$extraField}";
                $code .= "                if (!empty(\$data['{$extraInputName}'])) {\n";
                $code .= "                    \$pivot{$pivotModelShort}->{$extraField} = \$data['{$extraInputName}'];\n";
                $code .= "                }\n";
            }

            $code .= "                \$pivot{$pivotModelShort}->save();\n";
            $code .= "            }\n";

            $lines[] = $code;
        }

        return implode("\n\n", $lines);
    }

    /**
     * Genera código para actualizar registros pivote desde el update del modelo padre.
     * Se usa cuando el modelo padre tiene scoped relations (deudor, acreedor, etc.)
     * que apuntan a tablas pivote. Usa firstOrNew para encontrar o crear el registro pivote
     * basado en la clave del padre + el valor del scope (rol).
     */
    private function buildPivotUpdateFields(TableSchema $schema): string
    {
        $scopedRelations = $schema->scopedRelations();

        if (empty($scopedRelations)) {
            return '';
        }

        $lines = [];

        foreach ($scopedRelations as $col) {
            // Solo si la relación apunta a una tabla pivote con target resuelto
            if (!$col->pivotModel || !$col->pivotFk || !$col->scopeColumn || !$col->scopeValue || !$col->scopedTargetFk) {
                continue;
            }

            $pivotModel = $col->pivotModel;
            $pivotModelShort = class_basename($pivotModel);
            $fkColumn = $col->pivotFk;                  // parent FK (ej: Cobro_id)
            $scopeCol = $col->scopeColumn;
            $scopeVal = $col->scopeValue;
            $relationName = $col->relationName;
            $targetFk = $col->scopedTargetFk;            // target FK (ej: Cliente_id)
            $targetModelShort = $col->relatedModelName;  // target model (ej: Cliente)
            $extraFields = $col->pivotExtraFields ? json_decode($col->pivotExtraFields) : [];

            // Nombre del hidden input que contiene el ID del modelo destino
            // Convención: {relationName}_{scopedTargetFk} (ej: deudor_Cliente_id)
            $hiddenFkName = "{$relationName}_{$targetFk}";

            // Generar código para actualizar el registro pivote usando findOrFail
            $code = "            // Actualizar {$relationName}\n";
            $code .= "            \$related{$targetModelShort} = null;\n";
            $code .= "            if (!empty(\$data['{$hiddenFkName}'])) {\n";
            $ns = $this->getModelNamespace();
            $code .= "                \$related{$targetModelShort} = \\{$ns}{$targetModelShort}::findOrFail(\$data['{$hiddenFkName}']);\n";
            $code .= "            }\n";
            $code .= "            if (!empty(\$related{$targetModelShort})) {\n";
            $code .= "                \$pivot{$pivotModelShort} = \\{$ns}{$pivotModelShort}::firstOrNew([\n";
            $code .= "                    '{$fkColumn}' => \$" . $schema->modelVariable . "->id,\n";
            $code .= "                    '{$scopeCol}' => '{$scopeVal}',\n";
            $code .= "                ]);\n";
            $code .= "                \$pivot{$pivotModelShort}->{$targetFk} = \$related{$targetModelShort}->id;\n";

            // Actualizar campos extra del pivote (monto, etc.)
            foreach ($extraFields as $extraField) {
                $extraInputName = "{$relationName}_{$extraField}";
                $code .= "                if (!empty(\$data['{$extraInputName}'])) {\n";
                $code .= "                    \$pivot{$pivotModelShort}->{$extraField} = \$data['{$extraInputName}'];\n";
                $code .= "                }\n";
            }

            $code .= "                \$pivot{$pivotModelShort}->save();\n";
            $code .= "            }\n";

            $lines[] = $code;
        }

        return implode("\n\n", $lines);
    }

    private function buildUpdateFields(TableSchema $schema): string
    {
        $fragmentSimple      = $this->loadStub('fragments/update-field-simple.stub');
        $fragmentBuscador    = $this->loadStub('fragments/update-field-relation-buscador.stub');
        $fragmentFkSelect    = $this->loadStub('fragments/update-field-fk-select.stub');
        $fragmentPivotSync   = $this->loadStub('fragments/update-field-pivot-sync.stub');
        $lines               = [];

        foreach ($schema->editableColumns() as $col) {
            // Las relaciones scoped (deudor, acreedor) se manejan en pivotStoreFields
            if ($col->sqlType === 'special_relation') {
                continue;
            }

            if ($col->isForeignKey && $col->isPivotRelation) {
                // Patrón C: belongsToMany con wherePivot → sync()
                $buscadorName = $this->buscadorInputName($col);
                $sqlField     = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
                $line = str_replace('{{model}}',                $schema->modelVariable,            $fragmentPivotSync);
                $line = str_replace('{{RelatedModel}}',         $col->relatedModelName ?? '',       $line);
                $line = str_replace('{{related_var}}',          $col->relatedModelVariable ?? '',   $line);
                $line = str_replace('{{display_field}}',        $sqlField,                          $line);
                $line = str_replace('{{buscador_input_name}}',  $buscadorName,                      $line);
                $line = str_replace('{{input_name}}',           $buscadorName,                      $line);
                $line = str_replace('{{relation}}',             $col->relationName ?? '',            $line);
                $line = str_replace('{{pivot_column}}',         $col->pivotColumn ?? '',             $line);
                $line = str_replace('{{pivot_value}}',          $col->pivotValue ?? '',              $line);

            } elseif ($col->isForeignKey && $col->relationInputType === 'buscador') {
                // Buscador puro: solo llega el campo de texto namespaced
                $line = $this->fillRelationFragment($fragmentBuscador, $schema, $col);

            } elseif ($col->isForeignKey && $col->relationInputType === 'select') {
                // Select con opción "Agregar": puede llegar el id (select) O el texto (buscador secundario)
                $buscadorName = $this->buscadorInputName($col);
                $sqlField     = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');

                // Caso 1: llegó el id del select
                $lineSelect = str_replace('{{model}}',     $schema->modelVariable, $fragmentFkSelect);
                $lineSelect = str_replace('{{fk_column}}', $col->name,             $lineSelect);

                // Caso 2: llegó el texto del buscador secundario → firstOrCreate
                $lineBuscador = $this->fillRelationFragment($fragmentBuscador, $schema, $col);

                $line = $lineSelect . "\n" . $lineBuscador;

            } elseif ($col->isForeignKey && $col->relationInputType === 'link') {
                continue;
            } else {
                $line = str_replace('{{model}}', $schema->modelVariable, $fragmentSimple);
                $line = str_replace('{{field}}', $col->name,             $line);
            }
            $lines[] = rtrim($line);
        }

        return implode("\n", $lines);
    }

    private function buildEagerLoad(TableSchema $schema): string
    {
        $fragment = $this->loadStub('fragments/eager-load-line.stub');
        $lines    = [];

        foreach ($schema->eagerLoad as $relationPath) {
            $warning = $this->validateRelationPath($schema->modelClass, $relationPath);

            if ($warning !== null) {
                // Dejar el path comentado con una nota para que el desarrollador lo vea
                $lines[] = "            // [INVALID] '{$relationPath}' — {$warning}";
                continue;
            }

            $line    = str_replace('{{relation_path}}', $relationPath, $fragment);
            $lines[] = rtrim($line);
        }

        return implode("\n", $lines);
    }

    /**
     * Valida cada segmento de un eager load path recursivamente.
     *
     * Ejemplos:
     *   'ciudad'                           → válido si Contrato::ciudad() existe
     *   'participante_contratos.cliente'   → válido si Contrato::participante_contratos()
     *                                        existe Y ParticipanteContrato::cliente() existe
     *   'contratos.cliente'                → inválido si Unidad no tiene contratos()
     *                                        (tiene contratoVigente())
     *
     * Devuelve null si es válido, o un string con la razón del error.
     */
    private function validateRelationPath(string $modelClass, string $relationPath): ?string
    {
        $segments     = explode('.', $relationPath);
        $currentClass = $modelClass;

        foreach ($segments as $segment) {
            if (!class_exists($currentClass)) {
                return "clase '{$currentClass}' no encontrada";
            }

            $methods = $this->getModelRelationNames($currentClass);

            if (!in_array($segment, $methods)) {
                $shortClass = class_basename($currentClass);
                return "'{$shortClass}' no tiene el método '{$segment}()'";
            }

            // Avanzar al siguiente modelo de la cadena
            $nextClass = $this->resolveRelatedClass($currentClass, $segment);

            if ($nextClass === null) {
                // No pudimos resolver el siguiente modelo pero el método existe:
                // aceptar como válido (mejor ser permisivo que omitir en falso)
                break;
            }

            $currentClass = $nextClass;
        }

        return null;
    }

    /**
     * Instancia el modelo y ejecuta el método de relación para obtener
     * la clase del modelo relacionado.
     */
    private function resolveRelatedClass(string $modelClass, string $relationMethod): ?string
    {
        try {
            $instance = (new \ReflectionClass($modelClass))->newInstanceWithoutConstructor();
            $relation = $instance->$relationMethod();
            return get_class($relation->getRelated());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Devuelve los nombres de todos los métodos públicos sin parámetros
     * definidos directamente en el modelo (no heredados).
     * Son los candidatos a ser relaciones Eloquent.
     */
    private function getModelRelationNames(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        try {
            $reflection = new \ReflectionClass($modelClass);
            $names      = [];

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                // Solo métodos definidos en el modelo, no heredados de Model
                if ($method->getDeclaringClass()->getName() !== $modelClass) {
                    continue;
                }
                // Sin parámetros requeridos — las relaciones Eloquent no los tienen
                if ($method->getNumberOfRequiredParameters() > 0) {
                    continue;
                }
                $names[] = $method->getName();
            }

            return $names;
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildCatchConstraints(TableSchema $schema): string
    {
        if (empty($schema->checkConstraints)) {
            return '';
        }

        $fragment = $this->loadStub('fragments/catch-constraint.stub');
        $lines    = [];

        foreach ($schema->checkConstraints as $name => $message) {
            $line    = str_replace('{{constraint_name}}',    $name,    $fragment);
            $line    = str_replace('{{constraint_message}}', $message, $line);
            $lines[] = rtrim($line);
        }

        return implode("\n", $lines);
    }

    // ================================================================
    // FK DATA — variables count + options para show/create/edit
    // ================================================================

    private function buildFkData(TableSchema $schema): string
    {
        $fragment = $this->loadStub('fragments/fk-data-line.stub');
        $lines    = [];
        $seen     = [];

        foreach ($schema->columns as $col) {
            if (!$col->isForeignKey
                || ($col->isPrimaryKey && !$schema->isPivotTable)  // PK-FK incluido en pivotes
                || $col->isCalculated
            ) {
                continue;
            }

            $relatedVar = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');

            if (isset($seen[$relatedVar])) {
                continue;
            }
            $seen[$relatedVar] = true;

            $displayField = $col->relationDisplayField ?? 'id';
            $sqlField     = $this->displayFieldToSqlColumn($displayField);
            $isRelational = $this->displayFieldIsRelational($displayField);

            // Si el display_field cruza relaciones, no podemos usarlo en SQL
            // En ese caso ordenamos por id y dejamos que la vista acceda vía ->
            if ($isRelational) {
                $sqlField = 'id';
            } else {
                // Verificar que el campo existe como columna real en la tabla referenciada
                $referencedTable = $col->referencedTable ?? '';
                if ($referencedTable) {
                    $realColumns = $this->getTableColumnsForFk($referencedTable);
                    if (!empty($realColumns) && !in_array($sqlField, $realColumns)) {
                        $sqlField = 'id'; // fallback seguro
                    }
                }
            }

            $line = str_replace('{{related_var}}',   $relatedVar,                 $fragment);
            $line = str_replace('{{RelatedModel}}',  $col->relatedModelName ?? '', $line);
            $line = str_replace('{{display_field}}', $sqlField,                    $line);

            $lines[] = rtrim($line);
        }

        return implode("\n", $lines);
    }

    /**
     * Obtiene columnas reales de una tabla para validar display_field.
     * Usa DB si está disponible, o retorna array vacío como fallback.
     */
    private function getTableColumnsForFk(string $table): array
    {
        try {
            $database = config('database.connections.mysql.database');
            $rows     = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [$database, $table]);
            return array_map(fn($r) => $r->COLUMN_NAME, $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildFkCompact(TableSchema $schema): string
    {
        $vars = [];
        $seen = [];

        foreach ($schema->columns as $col) {
            if (!$col->isForeignKey
                || $col->isPrimaryKey
                || $col->isCalculated
            ) {
                continue;
            }

            $relatedVar = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');
            if (isset($seen[$relatedVar])) continue;
            $seen[$relatedVar] = true;

            $vars[] = "'{$relatedVar}Count'";
            $vars[] = "'{$relatedVar}Options'";
        }

        if (empty($vars)) return '';
        return ', ' . implode(', ', $vars);
    }

    private function buildFkCompactArray(TableSchema $schema): string
    {
        $fragment = $this->loadStub('fragments/fk-compact-array-line.stub');
        $lines    = [];
        $seen     = [];

        foreach ($schema->columns as $col) {
            if (!$col->isForeignKey
                || ($col->isPrimaryKey && !$schema->isPivotTable)  // PK-FK incluido en pivotes
                || $col->isCalculated
            ) {
                continue;
            }

            $relatedVar = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');
            if (isset($seen[$relatedVar])) continue;
            $seen[$relatedVar] = true;

            $line    = str_replace('{{related_var}}', $relatedVar, $fragment);
            $lines[] = rtrim($line);
        }

        return implode("\n", $lines);
    }

    private function renderRelationFkRow(TableSchema $schema, ColumnMetadata $col, string $label): string
    {
        $stub       = $this->loadStub('component-inline-relation-fk.stub');
        $fieldId    = $col->relationName ?? $col->name;
        $pascal     = PlaceholderRegistry::toPascal($fieldId);
        $relatedVar = $col->relatedModelVariable ?? Str::camel($col->relatedModelName ?? '');

        $displayField       = $col->relationDisplayField ?? 'id';
        $bladeDisplayField  = $this->displayFieldToBladeAccess($displayField);
        $sqlField           = $this->displayFieldToSqlColumn($displayField);
        $buscadorInputName  = $this->buscadorInputName($col);
        // For scoped relations, select name must match hidden FK key
        $scopedFkName = ($col->pivotModel !== null && $col->scopedTargetFk !== null)
            ? ($col->relationName . '_' . $col->scopedTargetFk)
            : $col->name;
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
        $pkSegments  = $pkStrategy->bladeUrlSegments();
        // Hidden FK input for scoped relations (buscador mode)
        $hiddenFkInput = ($col->pivotModel !== null && $col->scopedTargetFk !== null)
            ? '<input type="hidden" name="' . $scopedFkName . '" id="hidden-' . $schema->modelVariable . '-' . $pkSegments . '-' . $fieldId . '" value="">'
            : '';

        $stub = str_replace('{{label}}',                $label,                                                                $stub);
        $stub = str_replace('{{model}}',                $schema->modelVariable,                                               $stub);
        $stub = str_replace('{{field_id}}',             $fieldId,                                                             $stub);
        $stub = str_replace('{{FieldIdPascal}}',        $pascal,                                                              $stub);
        $stub = str_replace('{{related_route}}',        trim($col->relatedRoute ?? $col->referencedTable ?? '', '/'),         $stub);
        $stub = str_replace('{{related_var}}',          $relatedVar,                                                          $stub);
        $stub = str_replace('{{fk_column}}',            $col->name,                                                           $stub);
        $stub = str_replace('{{relation}}',             $col->relationName ?? '',                                              $stub);
        $stub = str_replace('{{display_field}}',        $bladeDisplayField,                                                   $stub);
        $stub = str_replace('{{buscador_input_name}}',  $buscadorInputName,                                                   $stub);
        $stub = str_replace('{{input_name}}',           $buscadorInputName,                                                   $stub); // legacy
        $stub = str_replace('{{route_base}}',           $schema->routeBase,                                                    $stub);
        $stub = str_replace('{{pk}}',                   $schema->primaryKey,                                                   $stub);
        $stub = str_replace('{{pk_blade_segments}}',   $pkSegments,                                                            $stub);
        $stub = str_replace('{{RelatedModel}}',         $col->relatedModelName ?? '',                                          $stub);
        $stub = str_replace('{{scoped_fk_name}}',       $scopedFkName,                                                        $stub);
        $stub = str_replace('{{hidden_fk_input}}',      $hiddenFkInput,                                                       $stub);

        return $stub;
    }



    // Tokens globales que se repiten en todos los stubs
    private function replaceGlobal(string $stub, TableSchema $schema): string
    {
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
        $stub = str_replace('{{Model}}',       $schema->modelName,     $stub);
        $stub = str_replace('{{model}}',       $schema->modelVariable, $stub);
        $stub = str_replace('{{models}}',      $schema->modelPlural,   $stub);
        $stub = str_replace('{{model_snake}}', $schema->modelSnake,    $stub);
        $stub = str_replace('{{model_title}}', $schema->modelTitle,    $stub);
        $stub = str_replace('{{route_base}}',  $schema->routeBase,     $stub);
        $stub = str_replace('{{pk}}',          $schema->primaryKey,    $stub);
        $stub = str_replace('{{pk_blade_segments}}', $pkStrategy->bladeUrlSegments(), $stub);
        return $stub;
    }

    // Tokens de campo que se repiten en todos los stubs de fila
    private function replaceFieldTokens(
        string         $stub,
        TableSchema    $schema,
        ColumnMetadata $col,
        string         $label
    ): string {
        $stub = str_replace('{{label}}',           $label,                               $stub);
        $stub = str_replace('{{model}}',       $schema->modelVariable,                $stub);
        $stub = str_replace('{{field}}',       $col->name,                            $stub);
        $stub = str_replace('{{route_base}}', $schema->routeBase,                    $stub);
$stub = str_replace('{{pk}}',          $schema->primaryKey,                   $stub);
        $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
        $stub = str_replace('{{pk_blade_segments}}', $pkStrategy->bladeUrlSegments(), $stub);
        return $stub;
    }

    private function fillRelationFragment(
        string         $fragment,
        TableSchema    $schema,
        ColumnMetadata $col
    ): string {
        $buscadorName = $this->buscadorInputName($col);
        $sqlField     = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');

        $line = str_replace('{{model}}',                $schema->modelVariable,            $fragment);
        $line = str_replace('{{RelatedModel}}',         $col->relatedModelName ?? '',       $line);
        $line = str_replace('{{related_var}}',          $col->relatedModelVariable ?? '',   $line);
        $line = str_replace('{{display_field}}',        $sqlField,                          $line);
        $line = str_replace('{{buscador_input_name}}',  $buscadorName,                      $line);
        $line = str_replace('{{input_name}}',           $buscadorName,                      $line); // legacy
        $line = str_replace('{{fk_column}}',            $col->name,                         $line);
        $line = str_replace('{{related_pk}}',           $col->referencedColumn ?? 'id',     $line);
        return $line;
    }

    // Resuelve la action del form para una relación buscador
    // FK simple:    /cliente/{{ $contrato->Ciudad_id }}
    // PK compuesta: /participanteContrato/{{ $contrato->id }}/{{ $contrato->arrendador->Cliente_id }}
    private function resolveFormAction(TableSchema $schema, ColumnMetadata $col): string
    {
        $model = $schema->modelVariable;

        // Relación especial: la action viene construida en config
        if ($col->sqlType === 'special_relation') {
            $route    = $col->relatedRoute;
            $relation = $col->relationName;
            $relPk    = $col->referencedColumn;
            return "{$route}/{{ \${$model}->id }}/{{ \${$model}->{$relation}->{$relPk} }}";
        }

        // FK simple
        $route = $col->referencedTable;
        return "{$route}/{{ \${$model}->{$col->name} }}";
    }

    // ================================================================
    // FILTER CONTROLLER
    // ================================================================

    public function renderFilterController(TableSchema $schema, array $scopedRelations = []): string
    {
        $stub = $this->loadStub('filter-controller.stub');
        $stub = $this->replaceGlobal($stub, $schema);
        $stub = str_replace('{{model_uses}}',      $this->buildModelUses($schema),          $stub);
        $stub = str_replace('{{filter_conditions}}', $this->buildFilterConditions($schema, $scopedRelations), $stub);
        return $stub;
    }

    // ================================================================
    // FILTER VIEW (panel colapsable)
    // ================================================================

    public function renderFilterView(TableSchema $schema, array $scopedRelations = []): string
    {
        $stub     = $this->loadStub('view-filter.stub');
        $stub     = $this->replaceGlobal($stub, $schema);
        $sections = $this->buildFilterSections($schema, $scopedRelations);
        $stub     = str_replace('{{filter_sections}}', implode("\n\n", $sections), $stub);
        return $stub;
    }

    // ================================================================
    // TABLE PARTIAL (para respuestas AJAX)
    // ================================================================

    public function renderTablePartial(TableSchema $schema): string
    {
        $stub = $this->loadStub('view-table-partial.stub');
        $stub = $this->replaceGlobal($stub, $schema);

        $cellFragment   = $this->loadStub('fragments/index-cell.stub');
        $cellFkFragment = $this->loadStub('fragments/index-cell-fk.stub');
        $cells          = [];

        foreach ($schema->fillableColumns() as $col) {
            if ($col->isForeignKey && $col->relationName) {
                $displayField  = $col->relationDisplayField ?? 'id';
                $bladeAccess   = $this->displayFieldToBladeAccess($displayField);
                $line = str_replace('{{model}}',         $schema->modelVariable,        $cellFkFragment);
                $line = str_replace('{{fk_column}}',     $col->name,                    $line);
                $line = str_replace('{{relation}}',      $col->relationName,            $line);
                $line = str_replace('{{display_field}}', $bladeAccess,                  $line);
                $line = str_replace('{{related_route}}', trim($col->relatedRoute ?? $col->referencedTable ?? '', '/'), $line);
                $cells[] = rtrim($line);
                continue;
            }
            if ($col->isForeignKey) continue;

            $line = str_replace('{{model}}', $schema->modelVariable, $cellFragment);
            $line = str_replace('{{field}}', $col->name,            $line);
            $cells[] = rtrim($line);
        }

        $stub = str_replace('{{index_cells}}', implode("\n", $cells), $stub);

        if ($schema->isCompositePk) {
            $pkStrategy = new PrimaryKeyStrategy($schema->primaryKeys, $schema->modelVariable);
            $stub = str_replace('{{pk_blade_segments}}', $pkStrategy->bladeUrlSegments(), $stub);
        } else {
            $stub = str_replace('{{pk_blade_segments}}', '{{ $' . $schema->modelVariable . '->' . $schema->primaryKeys[0] . ' }}', $stub);
        }

        return $stub;
    }

    // ================================================================
    // FILTER CONDITIONS (PHP code for FilterController)
    // ================================================================

    private function buildFilterConditions(TableSchema $schema, array $scopedRelations): string
    {
        $lines = [];

        // Columnas de la tabla (excluyendo PK, calculadas y special_relation)
        foreach ($schema->fillableColumns() as $col) {
            if ($col->isCalculated) continue;
            if ($col->isForeignKey && $col->sqlType === 'special_relation') continue; // scoped: handled separately

            $field = $col->name;

            // FK directa — poner ANTES que number porque son int pero deben tratarse como FK
            if ($col->isForeignKey) {
                $lines[] = "";
                $lines[] = "        // FK: {$field}";
                $lines[] = "        if (!empty(\$filter['{$field}'])) {";
                $lines[] = "            \$query->where('{$field}', \$filter['{$field}']);";
                $lines[] = "        }";
                continue;
            }

            // Texto (varchar, text, char)
            if (in_array($col->sqlType, ['varchar', 'text', 'mediumtext', 'longtext', 'char'])) {
                $lines[] = "";
                $lines[] = "        // Text: {$field}";
                $lines[] = "        if (!empty(\$filter['{$field}'])) {";
                $lines[] = "            \$query->where('{$field}', 'LIKE', \"%{\$filter['{$field}']}%\");";
                $lines[] = "        }";
                continue;
            }

            // Número (int, decimal, float, etc.)
            if (in_array($col->sqlType, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double']) && !$col->isBoolean) {
                $lines[] = "";
                $lines[] = "        // Number: {$field}";
                $lines[] = "        if (isset(\$filter['{$field}_min']) && \$filter['{$field}_min'] !== '') {";
                $lines[] = "            \$query->where('{$field}', '>=', \$filter['{$field}_min']);";
                $lines[] = "        }";
                $lines[] = "        if (isset(\$filter['{$field}_max']) && \$filter['{$field}_max'] !== '') {";
                $lines[] = "            \$query->where('{$field}', '<=', \$filter['{$field}_max']);";
                $lines[] = "        }";
                continue;
            }

            // Enum
            if ($col->sqlType === 'enum') {
                $lines[] = "";
                $lines[] = "        // Enum: {$field}";
                $lines[] = "        if (!empty(\$filter['{$field}'])) {";
                $lines[] = "            \$query->whereIn('{$field}', (array)\$filter['{$field}']);";
                $lines[] = "        }";
                continue;
            }

            // Boolean
            if ($col->isBoolean) {
                $lines[] = "";
                $lines[] = "        // Boolean: {$field}";
                $lines[] = "        if (isset(\$filter['{$field}']) && \$filter['{$field}'] !== '') {";
                $lines[] = "            \$query->where('{$field}', \$filter['{$field}']);";
                $lines[] = "        }";
                continue;
            }

            // Date / Datetime
            if (in_array($col->sqlType, ['date', 'datetime', 'timestamp'])) {
                $lines[] = "";
                $lines[] = "        // Date: {$field}";
                $lines[] = "        if (!empty(\$filter['{$field}_year'])) {";
                $lines[] = "            \$query->whereYear('{$field}', \$filter['{$field}_year']);";
                $lines[] = "        }";
                $lines[] = "        if (!empty(\$filter['{$field}_month'])) {";
                $lines[] = "            \$query->whereMonth('{$field}', \$filter['{$field}_month']);";
                $lines[] = "        }";
                $lines[] = "        if (!empty(\$filter['{$field}_from'])) {";
                $lines[] = "            \$query->whereDate('{$field}', '>=', \$filter['{$field}_from']);";
                $lines[] = "        }";
                $lines[] = "        if (!empty(\$filter['{$field}_to'])) {";
                $lines[] = "            \$query->whereDate('{$field}', '<=', \$filter['{$field}_to']);";
                $lines[] = "        }";
                continue;
            }

            // (FK directa ya se manejó antes del number)
        }

        // Scoped relations (deudor, acreedor, arrendador, etc.)
        foreach ($scopedRelations as $sr) {
            $relationName = $sr['relation_name'];
            $filterFk     = $sr['filter_fk'];
            $lines[] = "";
            $lines[] = "        // Scoped: {$relationName}";
            $lines[] = "        if (!empty(\$filter['{$relationName}_{$filterFk}'])) {";
            $lines[] = "            \$query->whereHas('{$relationName}', fn(\$q) => \$q->where('{$filterFk}', \$filter['{$relationName}_{$filterFk}']));";
            $lines[] = "        }";
        }

        return implode("\n", $lines);
    }

    // ================================================================
    // FILTER FIELDS (HTML for the filter panel)
    // ================================================================

    /**
     * @return string[] Array of section HTML blocks (collapsible groups)
     */
    private function buildFilterSections(TableSchema $schema, array $scopedRelations): array
    {
        // ── 1. Group fields by type ───────────────────────────────
        $groups = [
            'date'    => [],
            'enum'    => [],
            'number'  => [],
            'boolean' => [],
            'text'    => [],
            'fk'      => [],
        ];

        foreach ($schema->fillableColumns() as $col) {
            if ($col->isCalculated) continue;
            if ($col->isForeignKey && $col->sqlType === 'special_relation') continue;

            $label = $col->label ?? PlaceholderRegistry::toLabel($col->name);
            $width = $this->filterColumnWidth($col);

            if ($col->isForeignKey) {
                $groups['fk'][] = ['html' => $this->renderFilterFkField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            } elseif (in_array($col->sqlType, ['varchar', 'text', 'mediumtext', 'longtext', 'char'])) {
                $groups['text'][] = ['html' => $this->renderFilterTextField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            } elseif (in_array($col->sqlType, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double']) && !$col->isBoolean) {
                $groups['number'][] = ['html' => $this->renderFilterNumberField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            } elseif ($col->sqlType === 'enum') {
                $groups['enum'][] = ['html' => $this->renderFilterEnumField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            } elseif ($col->isBoolean) {
                $groups['boolean'][] = ['html' => $this->renderFilterBooleanField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            } elseif (in_array($col->sqlType, ['date', 'datetime', 'timestamp'])) {
                $groups['date'][] = ['html' => $this->renderFilterDateField($col, $label, $width), 'label' => $label, 'field' => $col->name];
            }
        }

        $scopedItems = [];
        foreach ($scopedRelations as $sr) {
            $scopedItems[] = ['html' => $this->renderFilterScopedField($sr), 'label' => $sr['label']];
        }

        // ── 2. Build collapsible sections ─────────────────────────
        $sections  = [];
        $modelSnake = $schema->modelSnake;

        // Multi-field groups: date, number, text, fk, boolean
        $filterTitles = config('generator.filter_titles', [
            'date'    => 'Filtrar por fechas',
            'number'  => 'Filtrar por montos',
            'text'    => 'Filtrar por texto',
            'fk'      => 'Filtrar por relaciones',
            'boolean' => 'Filtrar por opciones',
        ]);
        $multiGroups = [
            'date'    => ['icon' => '📅', 'title' => $filterTitles['date']],
            'number'  => ['icon' => '💰', 'title' => $filterTitles['number']],
            'text'    => ['icon' => '📝', 'title' => $filterTitles['text']],
            'fk'      => ['icon' => '🔗', 'title' => $filterTitles['fk']],
            'boolean' => ['icon' => '✅', 'title' => $filterTitles['boolean']],
        ];

        foreach ($multiGroups as $type => $info) {
            if (empty($groups[$type])) continue;
            $itemsHtml = [];
            foreach ($groups[$type] as $item) {
                $itemsHtml[] = $item['html'];
            }
            $sections[] = $this->renderCollapsibleSection(
                "fs-{$type}-{$modelSnake}",
                $info['icon'],
                $info['title'],
                implode("\n", $itemsHtml),
                $type === 'date' // dates expanded by default
            );
        }

        // Enum: each enum field gets its own section
        foreach ($groups['enum'] as $i => $item) {
            $sections[] = $this->renderCollapsibleSection(
                "fs-enum-{$i}-{$modelSnake}",
                '🏷️',
                "Filtrar por {$item['label']}",
                $item['html'],
                false
            );
        }

        // Scoped: each scoped relation gets its own section
        foreach ($scopedItems as $i => $item) {
            $sections[] = $this->renderCollapsibleSection(
                "fs-scoped-{$i}-{$modelSnake}",
                '👤',
                "Filtrar por {$item['label']}",
                $item['html'],
                false
            );
        }

        return $sections;
    }

    private function renderCollapsibleSection(string $id, string $icon, string $title, string $bodyHtml, bool $expanded): string
    {
        $show = $expanded ? ' show' : '';
        $expandedAttr = $expanded ? 'true' : 'false';
        return <<<HTML
            <div class="filter-group mb-2">
                <div class="filter-group-header d-flex align-items-center gap-2 px-2 py-1 rounded cursor-pointer"
                     data-bs-toggle="collapse"
                     data-bs-target="#{$id}"
                     role="button"
                     aria-expanded="{$expandedAttr}"
                     aria-controls="{$id}">
                    <span class="filter-group-icon">{$icon}</span>
                    <span class="filter-group-title fw-semibold small text-uppercase text-secondary">{$title}</span>
                    <i class="bi bi-chevron-down ms-auto filter-chevron small"></i>
                </div>
                <div class="collapse{$show}" id="{$id}">
                    <div class="row g-3 px-2 py-2">
                        {$bodyHtml}
                    </div>
                </div>
            </div>
HTML;
    }

    private function renderFilterTextField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub = $this->loadStub('fragments/filter-field-text.stub');
        return str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}'],
            [$label, $col->name, $width],
            $stub
        );
    }

    private function renderFilterNumberField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub = $this->loadStub('fragments/filter-field-number.stub');
        return str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}'],
            [$label, $col->name, $width],
            $stub
        );
    }

    private function renderFilterEnumField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub    = $this->loadStub('fragments/filter-field-enum.stub');
        $cbStub  = $this->loadStub('fragments/filter-enum-checkbox.stub');
        $checks  = [];

        foreach ($col->enumValues as $value) {
            $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $value);
            $check = str_replace(
                ['{{field}}', '{{enum_value}}', '{{enum_value_safe}}', '{{enum_label}}'],
                [$col->name, $value, $safe, $value],
                $cbStub
            );
            $checks[] = rtrim($check);
        }

        $stub = str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}', '{{enum_checkboxes}}'],
            [$label, $col->name, $width, implode("\n", $checks)],
            $stub
        );
        return $stub;
    }

    private function renderFilterBooleanField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub = $this->loadStub('fragments/filter-field-boolean.stub');
        return str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}'],
            [$label, $col->name, $width],
            $stub
        );
    }

    private function renderFilterDateField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub        = $this->loadStub('fragments/filter-field-date.stub');
        $monthStub   = $this->loadStub('fragments/filter-date-month-option.stub');
        $yearStub    = $this->loadStub('fragments/filter-date-year-option.stub');

        $months = config('generator.months', [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ]);

        $monthOpts = [];
        foreach ($months as $num => $name) {
            $monthOpts[] = rtrim(str_replace(
                ['{{month_num}}', '{{month_name}}'],
                [$num, $name],
                $monthStub
            ));
        }

        $yearOpts = [];
        $currentYear = (int) date('Y');
        for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
            $yearOpts[] = rtrim(str_replace('{{year}}', (string)$y, $yearStub));
        }

        $stub = str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}', '{{month_options}}', '{{year_options}}'],
            [$label, $col->name, $width, implode("\n", $monthOpts), implode("\n", $yearOpts)],
            $stub
        );
        return $stub;
    }

    private function renderFilterFkField(ColumnMetadata $col, string $label, string $width): string
    {
        $stub   = $this->loadStub('fragments/filter-field-fk.stub');
        $optStub = $this->loadStub('fragments/filter-fk-option.stub');

        $relatedModel = $col->relatedModelName ?? 'Unknown';
        $displayField = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');

        $optionCode = "@php(\${$col->relationName}Options = \\" . $this->getModelNamespace() . "{$relatedModel}::orderBy('{$displayField}')->get(['id', '{$displayField} as display']))\n";
        $optionCode .= "        @foreach(\${$col->relationName}Options as \$opt)\n";
        $optionCode .= rtrim(str_replace(
            ['{{option_id}}', '{{option_display}}'],
            ["{{ \$opt->id }}", "{{ \$opt->display }}"],
            $optStub
        )) . "\n";
        $optionCode .= "        @endforeach";

        $stub = str_replace(
            ['{{label}}', '{{field}}', '{{column_width}}', '{{fk_options}}'],
            [$label, $col->name, $width, $optionCode],
            $stub
        );
        return $stub;
    }

    private function renderFilterScopedField(array $sr): string
    {
        $stub   = $this->loadStub('fragments/filter-field-scoped.stub');
        $optStub = $this->loadStub('fragments/filter-scoped-option.stub');

        $label         = $sr['label'];
        $relationName  = $sr['relation_name'];
        $relatedModel  = $sr['related_model'];
        $displayField  = $sr['display_field'] ?? 'nombre';
        $shortModel    = class_basename($relatedModel);

        $optionCode = "@php(\${$relationName}Options = \\{$relatedModel}::orderBy('{$displayField}')->get(['id', '{$displayField} as display']))\n";
        $optionCode .= "        @foreach(\${$relationName}Options as \$opt)\n";
        $optionCode .= rtrim(str_replace(
            ['{{option_id}}', '{{option_display}}'],
            ["{{ \$opt->id }}", "{{ \$opt->display }}"],
            $optStub
        )) . "\n";
        $optionCode .= "        @endforeach";

        $targetFk = $sr['filter_fk'] ?? 'cliente_id';
        $stub = str_replace(
            ['{{label}}', '{{relation}}', '{{column_width}}', '{{scoped_options}}', '{{target_fk}}'],
            [$label, $relationName, 'col-md-3', $optionCode, $targetFk],
            $stub
        );
        return $stub;
    }

    private function filterColumnWidth(ColumnMetadata $col): string
    {
        // Date fields need more space
        if (in_array($col->sqlType, ['date', 'datetime', 'timestamp'])) {
            return 'col-md-6';
        }
        // Enum with many values needs more space
        if ($col->sqlType === 'enum' && count($col->enumValues) > 3) {
            return 'col-md-4';
        }
        return 'col-md-3';
    }

    /**
     * Obtiene el namespace de modelos desde config, con fallback para entornos
     * sin Laravel bootstrapped (ej: tests unitarios).
     */
    private function getModelNamespace(): string
    {
        try {
            return config('generator.model_namespace', 'App\\Models\\');
        } catch (\Throwable) {
            return 'App\\Models\\';
        }
    }

    private function loadStub(string $relativePath): string
    {
        $path = $this->stubsPath . '/' . $relativePath;

        if (!file_exists($path)) {
            throw new \RuntimeException("Stub no encontrado: {$path}");
        }

        return file_get_contents($path);
    }

    /**
     * Convierte un display_field con puntos a acceso Blade con flechas.
     * 'unidad.propiedad.direccion' → 'unidad->propiedad->direccion'
     * 'nombre' → 'nombre'
     */
    /**
     * Genera el name del input buscador para evitar colisión con campos propios.
     * Usa relationName como discriminador (único por FK) con fallback a referencedTable.
     * Convención: {display_field_sql}-{discriminator}
     * Ejemplo: 'nombre-deudor', 'nombre-acreedor', 'direccion-propiedad'
     */
    private function buscadorInputName(ColumnMetadata $col): string
    {
        $sqlField     = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
        $discriminator = $col->relationName ?? $col->referencedTable ?? $col->relatedRoute ?? 'rel';
        return "{$sqlField}-{$discriminator}";
    }

    private function displayFieldToBladeAccess(string $displayField): string
    {
        return str_replace('.', '->', $displayField);
    }

    /**
     * Extrae solo el último segmento del display_field para SQL.
     * 'unidad.propiedad.direccion' → 'direccion'
     * 'nombre' → 'nombre'
     */
    private function displayFieldToSqlColumn(string $displayField): string
    {
        $parts = explode('.', $displayField);
        return end($parts);
    }

    /**
     * Devuelve true si el display_field cruza relaciones (tiene puntos).
     */
    private function displayFieldIsRelational(string $displayField): bool
    {
        return str_contains($displayField, '.');
    }
}
