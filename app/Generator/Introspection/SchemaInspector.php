<?php

namespace App\Generator\Introspection;

use Illuminate\Support\Facades\DB;

/**
 * Lee information_schema de MySQL y construye ColumnMetadata por columna.
 * No toca los modelos Eloquent — eso es responsabilidad de RelationResolver.
 */
class SchemaInspector
{
    private string $database;

    public function __construct()
    {
        $this->database = config('database.connections.mysql.database');
    }

    /**
     * Retorna todas las columnas de una tabla como array de ColumnMetadata.
     *
     * @return ColumnMetadata[]
     */
    public function getColumns(string $table, array $fieldConfig = []): array
    {
        $rows = DB::select("
            SELECT
                c.COLUMN_NAME,
                c.DATA_TYPE,
                c.CHARACTER_MAXIMUM_LENGTH,
                c.IS_NULLABLE,
                c.COLUMN_DEFAULT,
                c.COLUMN_TYPE,
                c.COLUMN_KEY,
                c.EXTRA,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME
            FROM information_schema.COLUMNS c
            LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu
                ON kcu.TABLE_SCHEMA = c.TABLE_SCHEMA
                AND kcu.TABLE_NAME  = c.TABLE_NAME
                AND kcu.COLUMN_NAME = c.COLUMN_NAME
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            WHERE c.TABLE_SCHEMA = ?
              AND c.TABLE_NAME   = ?
            ORDER BY c.ORDINAL_POSITION
        ", [$this->database, $table]);

        $columns = [];

        foreach ($rows as $row) {
            $columns[] = $this->buildColumnMetadata($row, $table, $fieldConfig);
        }

        return $columns;
    }

    /**
     * Retorna las FK de una tabla como array indexado por nombre de columna.
     * Ejemplo: ['Propiedad_id' => ['table' => 'propiedad', 'column' => 'id']]
     */
    public function getForeignKeys(string $table): array
    {
        $rows = DB::select("
            SELECT
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA           = ?
              AND kcu.TABLE_NAME             = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ", [$this->database, $table]);

        $fks = [];
        foreach ($rows as $row) {
            $fks[$row->COLUMN_NAME] = [
                'table'  => $row->REFERENCED_TABLE_NAME,
                'column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }

        return $fks;
    }

    /**
     * Retorna los nombres de las CHECK constraints de una tabla.
     * Clave = nombre del constraint, valor = cláusula SQL.
     */
    public function getCheckConstraints(string $table): array
    {
        $rows = DB::select("
            SELECT
                cc.CONSTRAINT_NAME,
                cc.CHECK_CLAUSE
            FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.CHECK_CONSTRAINTS cc
                ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                AND cc.CONSTRAINT_NAME  = tc.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA    = ?
              AND tc.TABLE_NAME      = ?
              AND tc.CONSTRAINT_TYPE = 'CHECK'
        ", [$this->database, $table]);

        $constraints = [];
        foreach ($rows as $row) {
            $constraints[$row->CONSTRAINT_NAME] = $row->CHECK_CLAUSE;
        }

        return $constraints;
    }

    /**
     * Retorna la(s) columna(s) que forman la PK de una tabla.
     *
     * @return string[]
     */
    public function getPrimaryKeys(string $table): array
    {
        $rows = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA  = ?
              AND TABLE_NAME    = ?
              AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ", [$this->database, $table]);

        return array_map(fn($r) => $r->COLUMN_NAME, $rows);
    }

    // ──────────────────────────────────────────────────────────────────
    // BUILDER PRIVADO
    // ──────────────────────────────────────────────────────────────────

    private function buildColumnMetadata(object $row, string $table, array $fieldConfig): ColumnMetadata
    {
        $name      = $row->COLUMN_NAME;
        $sqlType   = strtolower($row->DATA_TYPE);
        $colType   = $row->COLUMN_TYPE; // 'tinyint(1)', 'enum(...)', 'ENUM(...)'
        $nullable  = $row->IS_NULLABLE === 'YES';
        $isPk      = $row->COLUMN_KEY === 'PRI';
        $isUnique  = $row->COLUMN_KEY === 'UNI';
        $maxLength = $row->CHARACTER_MAXIMUM_LENGTH ? (int) $row->CHARACTER_MAXIMUM_LENGTH : null;
        $isFk      = $row->REFERENCED_TABLE_NAME !== null;

        $isBoolean   = ($sqlType === 'tinyint' && str_contains(strtolower($colType), '(1)'));
        $enumValues  = $this->parseEnumValues($colType);
        $htmlInput   = $this->resolveHtmlInputType($sqlType, $isBoolean, $isFk);

        // Config del campo en generator.php (puede sobrescribir defaults)
        $cfg = $fieldConfig[$name] ?? [];

        // Editabilidad: por defecto editable si no es PK ni calculado
        // PK-FK (pivot tables) deben ser editables
        $isEditable   = $cfg['editable'] ?? (!$isPk || $isFk);
        $isCalculated = $cfg['calculated'] ?? false;

        // Label: del config o generado automáticamente
        $label = $cfg['label'] ?? $this->generateLabel($name);

        // Metadata de relación FK
        $relatedModelName     = null;
        $relatedModelVariable = null;
        $relationName         = null;
        $relationDisplayField = null;
        $relationInputType    = null;
        $relationInputName    = null;

        if ($isFk) {
            $relatedTable         = $row->REFERENCED_TABLE_NAME;
            $relatedModelName     = $cfg['related_model']   ?? $this->tableToModelName($relatedTable);
            $relatedModelVariable = $cfg['related_var']     ?? strtolower($relatedModelName);
            $relationName         = $cfg['relation_name']   ?? $this->columnToRelationName($name);
            
            // Fallback inteligente: si la tabla tiene columna 'nombre', usarla
            $defaultDisplayField = $cfg['display_field'] ?? $this->guessDisplayField($relatedTable);
            $relationDisplayField = $defaultDisplayField;
            $relationInputType    = $cfg['input_type']      ?? 'buscador';
            $relationInputName    = $cfg['input_name']      ?? $relationDisplayField;
        }

        return new ColumnMetadata(
            table:                $table,
            name:                 $name,
            sqlType:              $sqlType,
            nullable:             $nullable,
            isPrimaryKey:         $isPk,
            isUnique:             $isUnique,
            maxLength:            $maxLength,
            isBoolean:            $isBoolean,
            enumValues:           $enumValues,
            htmlInputType:        $htmlInput,
            isForeignKey:         $isFk,
            referencedTable:      $row->REFERENCED_TABLE_NAME,
            referencedColumn:     $row->REFERENCED_COLUMN_NAME,
            relatedModelName:     $relatedModelName,
            relatedModelVariable: $relatedModelVariable,
            relationName:         $relationName,
            relationDisplayField: $relationDisplayField,
            relationInputType:    $relationInputType,
            relationInputName:    $relationInputName,
            isEditable:           $isEditable,
            isCalculated:         $isCalculated,
            label:                $label,
            // relatedRoute siempre igual a referencedTable para FKs normales
            // puede ser sobreescrito por config['related_route'] en SchemaBuilder
            relatedRoute:         trim($cfg['related_route'] ?? $row->REFERENCED_TABLE_NAME, '/'),
        );
    }

    // ──────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────

    private function resolveHtmlInputType(string $sqlType, bool $isBoolean, bool $isFk): string
    {
        if ($isBoolean)  return 'select';
        if ($isFk)       return 'text';   // buscador usa text

        return match($sqlType) {
            'int', 'bigint', 'smallint', 'tinyint',
            'decimal', 'float', 'double'  => 'number',
            'date'                         => 'date',
            'datetime', 'timestamp'        => 'datetime-local',
            'enum'                         => 'select',
            default                        => 'text',
        };
    }

    private function parseEnumValues(string $columnType): array
    {
        // COLUMN_TYPE para enum: "enum('Arrendador','Arrendatario')"
        if (!str_starts_with(strtolower($columnType), 'enum(')) {
            return [];
        }

        preg_match_all("/'([^']+)'/", $columnType, $matches);
        return $matches[1] ?? [];
    }

    private function generateLabel(string $columnName): string
    {
        // 'comision_mensual' → 'Comisión Mensual'
        // 'Ciudad_id'        → 'Ciudad'
        $name = preg_replace('/_id$/i', '', $columnName);
        $name = str_replace('_', ' ', $name);
        return ucwords(strtolower($name));
    }

    private function tableToModelName(string $table): string
    {
        // 'participante_contrato' → 'ParticipanteContrato'
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
    }

    private function columnToRelationName(string $column): string
    {
        // 'Ciudad_id' → 'ciudad'
        return strtolower(preg_replace('/_id$/i', '', $column));
    }

    private function guessDisplayField(string $table): string
    {
        // Patterns comunes para display field (name-first: neutral default)
        $commonFields = ['name', 'nombre', 'razon_social', 'nombre_completo', 'descripcion', 'titulo'];
        
        try {
            $database = config('database.connections.mysql.database');
            $columns = \Illuminate\Support\Facades\DB::select("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
                LIMIT 10
            ", [$database, $table]);
            
            $colNames = array_map(fn($c) => strtolower($c->COLUMN_NAME), $columns);
            
            // Buscar primer campo común que exista
            foreach ($commonFields as $field) {
                if (in_array($field, $colNames)) {
                    return $field;
                }
            }
            
            // Default: id
            return 'id';
        } catch (\Throwable $e) {
            return 'id';
        }
    }
}
