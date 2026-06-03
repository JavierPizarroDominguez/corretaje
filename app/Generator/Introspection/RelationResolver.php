<?php

namespace App\Generator\Introspection;

use ReflectionMethod;
use ReflectionClass;

/**
 * Lee el modelo Eloquent existente (generado por Reliese) via Reflection
 * y extrae sus relaciones sin volver a parsear SQL.
 *
 * NO modifica ni regenera modelos.
 */
class RelationResolver
{
    // Métodos de Eloquent que definen relaciones
    private const RELATION_METHODS = [
        'belongsTo', 'hasMany', 'hasOne',
        'belongsToMany', 'hasManyThrough', 'hasOneThrough',
        'morphTo', 'morphMany', 'morphOne',
    ];

    /**
     * Retorna las relaciones detectadas del modelo.
     *
     * @param  string $modelClass  Ej: 'App\Models\Contrato'
     * @return RelationMetadata[]
     */
    public function resolve(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("Modelo no encontrado: {$modelClass}");
        }

        $reflection = new ReflectionClass($modelClass);
        $relations  = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Solo métodos propios del modelo, no heredados de Model
            if ($method->getDeclaringClass()->getName() !== $modelClass) {
                continue;
            }

            // Sin parámetros requeridos (las relaciones no los tienen)
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $type = $this->detectRelationType($method);

            if ($type === null) {
                continue;
            }

            $relations[] = $this->buildRelationMetadata($method, $type, $modelClass);
        }

        return $relations;
    }

    /**
     * Retorna solo las relaciones sugeridas para eager loading en show().
     * Incluye belongsTo (para mostrar datos) y hasMany (para listar).
     *
     * @return string[]  Paths de eager load: ['unidad', 'participante_contratos.cliente']
     */
    public function getEagerLoadSuggestions(string $modelClass, array $overrides = []): array
    {
        // Si hay override manual en config, usar ese directamente
        if (!empty($overrides)) {
            return $overrides;
        }

        $relations = $this->resolve($modelClass);
        $paths     = [];

        foreach ($relations as $rel) {
            if (!$rel->suggestEagerLoad) {
                continue;
            }

            if ($rel->eagerLoadNested) {
                $paths[] = $rel->name . '.' . $rel->eagerLoadNested;
            } else {
                $paths[] = $rel->name;
            }
        }

        return $paths;
    }

    /**
     * Retorna las relaciones "scoped" (hasOne + where) del modelo.
     * Estas son las relaciones especiales como deudor, acreedor, arrendador, etc.
     * que apuntan a tablas pivote con un rol discriminador.
     *
     * @return array[]  [
     *   'deudor' => [
     *     'type' => 'hasOne-scoped',
     *     'related' => 'App\Models\ParticipanteCobro',
     *     'relatedTable' => 'participante_cobro',
     *     'foreignKey' => 'Cobro_id',
     *     'localKey' => 'id',
     *     'scopeColumn' => 'rol',
     *     'scopeValue' => 'Deudor',
     *     'isPivotTable' => true,
     *     'pivotExtraFields' => ['monto'],
     *   ],
     *   ...
     * ]
     */
    public function getScopedRelations(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $reflection = new ReflectionClass($modelClass);
        $scoped = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $modelClass) {
                continue;
            }

            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $source = $this->getMethodSource($method);
            if ($source === null) {
                continue;
            }

            // Detectar solo hasOne con where (ambos deben estar presentes)
            if (!str_contains($source, '$this->hasOne(') && !str_contains($source, '->where(')) {
                continue;
            }

            // Extraer el modelo relacionado
            preg_match('/\$this->hasOne\s*\(\s*([A-Za-z]+)::class/', $source, $m);
            if (empty($m[1])) {
                continue;
            }

            $relatedShort = $m[1];
            $relatedModel = $this->resolveModelClass($relatedShort, $modelClass);

            if (!class_exists($relatedModel)) {
                continue;
            }

            // Extraer FK de la relación
            $relatedInstance = new $relatedModel();
            $foreignKey = $relatedInstance->getForeignKey();

            // Extraer scope column y value del where()
            $scopeColumn = null;
            $scopeValue = null;

            // Buscar ->where('column', 'value') o ->where('column', '=', 'value')
            if (preg_match("/->where\s*\(\s*['\"](\w+)['\"]\s*,\s*['\"](\w+)['\"]\s*\)/", $source, $wm)) {
                $scopeColumn = $wm[1];
                $scopeValue = $wm[2];
            } elseif (preg_match("/->where\s*\(\s*['\"](\w+)['\"]\s*,\s*=\s*,\s*['\"](\w+)['\"]\s*\)/", $source, $wm)) {
                $scopeColumn = $wm[1];
                $scopeValue = $wm[2];
            }

            if ($scopeColumn === null) {
                continue;
            }

            // Verificar si el modelo relacionado es una tabla pivote
            $relatedTable = $relatedInstance->getTable();
            $isPivotTable = $this->isPivotTable($relatedModel);

            // Obtener campos extra del modelo pivote (no PK, no FK-PK)
            $pivotExtraFields = [];
            $parentFk = null;
            $targetFk = null;
            $targetModel = null;
            $targetTable = null;

            if ($isPivotTable) {
                $pivotExtraFields = $this->getPivotExtraFields($relatedModel);

                // Resolver belongsTo del pivote para obtener FKs explícitos
                $pivotRelations = $this->resolve($relatedModel);
                $parentFk = null;
                $targetFk = null;
                $targetModel = null;

                foreach ($pivotRelations as $pivotRel) {
                    if ($pivotRel->type !== 'belongsTo') {
                        continue;
                    }

                    if ($pivotRel->relatedModel === $modelClass) {
                        // Este belongsTo apunta al modelo padre (Cobro)
                        $parentFk = $pivotRel->foreignKey;
                    } else {
                        // Este belongsTo apunta al modelo destino (Cliente)
                        $targetFk = $pivotRel->foreignKey;
                        $targetModel = $pivotRel->relatedModel;
                    }
                }

                // Usar el FK explícito desde belongsTo del pivote, no getForeignKey()
                if ($parentFk !== null) {
                    $foreignKey = $parentFk;
                }

                if ($targetModel !== null && class_exists($targetModel)) {
                    $targetInstance = new $targetModel();
                    $targetTable = $targetInstance->getTable();
                }
            }

            $scoped[$method->getName()] = [
                'type' => 'hasOne-scoped',
                'related' => $relatedModel,
                'relatedTable' => $relatedTable,
                'foreignKey' => $foreignKey,
                'localKey' => $relatedInstance->getKeyName(),
                'scopeColumn' => $scopeColumn,
                'scopeValue' => $scopeValue,
                'isPivotTable' => $isPivotTable,
                'pivotExtraFields' => $pivotExtraFields,
                'parentFk' => $parentFk,
                'targetFk' => $targetFk,
                'targetModel' => $targetModel,
                'targetTable' => $targetTable,
            ];
        }

        return $scoped;
    }

    /**
     * Determina si un modelo corresponde a una tabla pivote.
     * Una tabla pivote tiene: PK compuesta + todas las PK son FKs + no auto-increment.
     */
    private function isPivotTable(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $model = new $modelClass();

        // Verificar si no es incrementing (PK compuesta)
        if ($model->getIncrementing()) {
            return false;
        }

        // Obtener la tabla y sus columnas PK
        $table = $model->getTable();

        try {
            $database = config('database.connections.mysql.database');
            $pks = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
            ", [$database, $table]);

            if (count($pks) < 2) {
                return false;
            }

            // Obtener FKs de la tabla
            $fks = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            $fkColumns = array_map(fn($f) => $f->COLUMN_NAME, $fks);

            // Todas las PKs deben ser también FKs
            $pkColumns = array_map(fn($p) => $p->COLUMN_NAME, $pks);

            return empty(array_diff($pkColumns, $fkColumns));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obtiene las columnas extra de una tabla pivote (no PK, no FK-PK).
     */
    private function getPivotExtraFields(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $model = new $modelClass();
        $table = $model->getTable();

        try {
            $database = config('database.connections.mysql.database');
            $columns = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [$database, $table]);

            $pks = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND KEY_NAME = 'PRIMARY'
            ", [$database, $table]);

            $fkColumns = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            $pkCols = array_map(fn($p) => $p->COLUMN_NAME, $pks);
            $fkCols = array_map(fn($f) => $f->COLUMN_NAME, $fks);
            $pkFkCols = array_intersect($pkCols, $fkCols);

            $extraCols = [];
            foreach ($columns as $col) {
                $colName = $col->COLUMN_NAME;
                if (!in_array($colName, $pkCols) && !in_array($colName, $pkFkCols)) {
                    $extraCols[] = $colName;
                }
            }

            return $extraCols;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIVADOS
    // ──────────────────────────────────────────────────────────────────

    private function detectRelationType(ReflectionMethod $method): ?string
    {
        $source = $this->getMethodSource($method);

        if ($source === null) {
            return null;
        }

        foreach (self::RELATION_METHODS as $relationMethod) {
            if (str_contains($source, "\$this->{$relationMethod}(")) {
                // Caso especial: hasOne con ->where() es una relación escoped
                if ($relationMethod === 'hasOne' && str_contains($source, '->where(')) {
                    return 'hasOne-scoped';
                }
                return $relationMethod;
            }
        }

        return null;
    }

    private function buildRelationMetadata(
        ReflectionMethod $method,
        string $type,
        string $modelClass
    ): RelationMetadata {
        $name   = $method->getName();
        $source = $this->getMethodSource($method) ?? '';

        // Extraer el modelo relacionado del source
        // Ej: $this->belongsTo(Ciudad::class, 'Ciudad_id')
        preg_match('/(?:belongsTo|hasMany|hasOne|belongsToMany)\s*\(\s*([A-Za-z]+)::class/', $source, $m);
        $relatedShort = $m[1] ?? 'Unknown';
        $relatedModel = $this->resolveModelClass($relatedShort, $modelClass);

        // Extraer FK del source si aparece
        preg_match('/[\'"]([A-Za-z_]+_id)[\'"]/', $source, $fkMatch);
        $foreignKey = $fkMatch[1] ?? null;

        // Decidir si se sugiere para eager load y con qué profundidad
        [$suggestEager, $nestedPath] = $this->resolveEagerLoadStrategy($type, $name, $relatedModel);

        return new RelationMetadata(
            name:               $name,
            type:               $type,
            relatedModel:       $relatedModel,
            relatedModelShort:  $relatedShort,
            foreignKey:         $foreignKey,
            suggestEagerLoad:   $suggestEager,
            eagerLoadNested:    $nestedPath,
        );
    }

    private function resolveEagerLoadStrategy(string $type, string $name, string $relatedModel): array
    {
        switch ($type) {
            case 'belongsTo':
                // Siempre eager load: se necesita para mostrar datos en la vista
                return [true, null];

            case 'hasMany':
                // Usar detección estructural consistente con getScopedRelations()
                $isJoinTable = $this->isPivotTable($relatedModel);

                return [true, $isJoinTable ? 'cliente' : null];

            case 'hasOne':
            case 'hasOne-scoped':
                return [true, null];

            case 'belongsToMany':
                return [true, null];

            default:
                return [false, null];
        }
    }

    private function getMethodSource(ReflectionMethod $method): ?string
    {
        $file  = $method->getFileName();
        $start = $method->getStartLine();
        $end   = $method->getEndLine();

        if (!$file || !$start || !$end) {
            return null;
        }

        $lines = file($file);
        return implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }

    private function resolveModelClass(string $shortName, string $contextClass): string
    {
        // Intentar en el mismo namespace que el modelo actual
        $ns = (new ReflectionClass($contextClass))->getNamespaceName();
        $candidate = $ns . '\\' . $shortName;

        if (class_exists($candidate)) {
            return $candidate;
        }

        return config('generator.model_namespace', 'App\\Models\\') . $shortName;
    }
}
