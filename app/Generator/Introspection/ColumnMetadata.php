<?php

namespace App\Generator\Introspection;

/**
 * DTO inmutable que representa una columna de la BD.
 * El SchemaInspector construye estas instancias.
 * El StubRenderer las consume para generar código.
 */
class ColumnMetadata
{
    public function __construct(
        // ── Identidad ─────────────────────────────────────────────
        public readonly string  $table,
        public readonly string  $name,

        // ── Tipo SQL raw (DATA_TYPE de information_schema) ─────────
        // 'int' | 'varchar' | 'decimal' | 'date' | 'datetime' | 'enum' | 'tinyint'
        public readonly string  $sqlType,

        // ── Restricciones básicas ──────────────────────────────────
        public readonly bool    $nullable,
        public readonly bool    $isPrimaryKey,
        public readonly bool    $isUnique,

        // ── Tamaño ────────────────────────────────────────────────
        public readonly ?int    $maxLength,

        // ── Tipos especiales ───────────────────────────────────────
        public readonly bool    $isBoolean,     // tinyint(1) tratado como bool
        public readonly array   $enumValues,    // ['Arrendador', 'Arrendatario'] para ENUM

        // ── Tipo del input HTML ────────────────────────────────────
        // 'text' | 'number' | 'date' | 'datetime-local' | 'select'
        public readonly string  $htmlInputType,

        // ── Foreign Key ────────────────────────────────────────────
        public readonly bool    $isForeignKey,
        public readonly ?string $referencedTable,
        public readonly ?string $referencedColumn,

        // ── Metadata de relación (cuando isForeignKey = true) ─────
        public readonly ?string $relatedModelName,       // 'Ciudad'
        public readonly ?string $relatedModelVariable,   // 'ciudad'
        public readonly ?string $relationName,           // método en el modelo: 'ciudad'
        public readonly ?string $relationDisplayField,   // campo a mostrar: 'nombre'
        public readonly ?string $relationInputType,      // 'buscador' | 'select' | 'link'
        public readonly ?string $relationInputName,      // name del input HTML

        // ── Comportamiento en generación ──────────────────────────
        public readonly bool    $isEditable,    // genera botón Editar en la vista
        public readonly bool    $isCalculated,  // accessor Eloquent, no columna real

        // ── Label legible para la vista ───────────────────────────
        public readonly string  $label,         // 'Comisión Mensual'

        // ── Relación pivot (belongsToMany con wherePivot) ─────────
        // Opción C: arrendador/arrendatario via belongsToMany + sync()
        public readonly bool    $isPivotRelation  = false,
        public readonly ?string $pivotColumn      = null,  // columna discriminadora: 'rol'
        public readonly ?string $pivotValue       = null,  // valor del filtro: 'Arrendador'
        public readonly ?string $relatedRoute     = null,  // ruta del modelo relacionado

        // ── Relación scoped (hasOne + where) ────────────────────────
        // Pattern A: deudor, acreedor, arrendador, etc. en modelos padre
        // Apuntan a tablas pivote con rol discriminador
        public readonly ?string $scopeColumn      = null,  // columna del where: 'rol'
        public readonly ?string $scopeValue       = null,  // valor del where: 'Deudor'
        public readonly ?string $pivotModel       = null,  // modelo pivote: 'App\Models\ParticipanteCobro'
        public readonly ?string $pivotFk         = null,  // FK en pivote hacia este modelo: 'Cobro_id'
        public readonly ?string $pivotExtraFields = null,  // campos extra del pivote (json): '["monto"]'

        // ── Scoped relation target FK (resuelto desde belongsTo del pivote) ───
        // Para relaciones scoped (deudor, acreedor), apunta al FK del modelo
        // destino en la tabla pivote (ej: 'Cliente_id' en participante_cobro)
        public readonly ?string $scopedTargetFk = null,
    ) {}

    /**
     * Factory para campos calculados (accessors Eloquent).
     * No vienen de information_schema, se declaran en config/generator.php.
     */
    public static function calculated(string $table, string $name, string $label): self
    {
        return new self(
            table:                $table,
            name:                 $name,
            sqlType:              'virtual',
            nullable:             true,
            isPrimaryKey:         false,
            isUnique:             false,
            maxLength:            null,
            isBoolean:            false,
            enumValues:           [],
            htmlInputType:        'text',
            isForeignKey:         false,
            referencedTable:      null,
            referencedColumn:     null,
            relatedModelName:     null,
            relatedModelVariable: null,
            relationName:         null,
            relationDisplayField: null,
            relationInputType:    null,
            relationInputName:    null,
            isEditable:           false,
            isCalculated:         true,
            label:                $label,
        );
    }
}
