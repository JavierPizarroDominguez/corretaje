<?php

namespace App\Generator\Schema;

use App\Generator\Introspection\ColumnMetadata;

class TableSchema
{
    // ── Tabla ────────────────────────────────────────────────────────
    public string $table;

    // ── Modelo ───────────────────────────────────────────────────────
    public string $modelClass;       // App\Models\Contrato
    public string $modelName;        // Contrato
    public string $modelVariable;    // contrato
    public string $modelPlural;      // contratos
    public string $modelSnake;       // contrato  (nombre de carpeta de vistas)
    public string $modelTitle;       // contrato  (para mensajes flash)

    // ── Primary Key ──────────────────────────────────────────────────
    public string $primaryKey;       // 'id' simple, o 'contrato_id_cliente_id' compuesta (rara vez se usa raw)
    public bool   $isCompositePk;
    public array  $primaryKeys;      // ['id'] o ['Contrato_id', 'Cliente_id']

    // ── Tabla pivote ──────────────────────────────────────────────────
    public bool $isPivotTable = false;   // true si PK compuesta y todas las PKs son FKs

    // ── Rutas ────────────────────────────────────────────────────────
    public string $routeBase;        // 'contrato'

    // ── Columnas (en orden de la tabla + relaciones especiales al final) ──
    /** @var ColumnMetadata[] */
    public array $columns;

    // ── Eager loading para show() ─────────────────────────────────────
    public array $eagerLoad;         // ['unidad', 'ciudad', 'participante_contratos.cliente']

    // ── CHECK constraints con mensajes legibles ───────────────────────
    public array $checkConstraints;  // ['chk_comision_mensual_contrato' => 'La renta no puede...']

    // ── Agrupación en components Blade ────────────────────────────────
    // Clave: nombre del component. Valor: ['campo1', 'campo2'] o '__all__'
    public array $components;

    public function __construct()
    {
        $this->columns          = [];
        $this->eagerLoad        = [];
        $this->checkConstraints = [];
        $this->components       = [];
        $this->isCompositePk    = false;
        $this->primaryKeys      = [];
        $this->isPivotTable     = false;
    }

    /**
     * Columnas editables: excluye PK, calculadas y no editables.
     * Usadas para buildUpdateFields().
     *
     * @return ColumnMetadata[]
     */
    public function editableColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) => $c->isEditable && $c->isCalculated
                ? false
                : ($c->isPrimaryKey
                    ? ($this->isPivotTable && $c->isForeignKey)
                    : true)
        ));
    }

    /**
     * Columnas fillables: todas menos PK y calculadas.
     * Para tablas pivote, incluye PK-FK porque son editables (seleccionar relaciones).
     *
     * @return ColumnMetadata[]
     */
    public function fillableColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) => $c->isCalculated
                ? false
                : ($c->isPrimaryKey
                    ? ($this->isPivotTable && $c->isForeignKey)  // PK-FK editable en pivotes
                    : true)
        ));
    }

    /**
     * Columnas de relaciones buscador.
     * Usadas para generar las llamadas buscador() en @push('scripts').
     *
     * @return ColumnMetadata[]
     */
    public function buscadorColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) => $c->isForeignKey && $c->relationInputType === 'buscador'
        ));
    }

    /**
     * Columnas que son simultáneamente Primary Key y Foreign Key.
     * Propias de tablas pivote (ej: ParticipanteContrato tiene Contrato_id y Cliente_id como PK y FK).
     *
     * @return ColumnMetadata[]
     */
    public function pivotKeyColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) => $c->isPrimaryKey && $c->isForeignKey
        ));
    }

    /**
     * Columnas extra de una tabla pivote (no PK, no FK-PK, editables).
     * Ej: ParticipanteContrato tiene 'rol' y 'monto'.
     *
     * @return ColumnMetadata[]
     */
    public function pivotExtraColumns(): array
    {
        $pivotKeys = array_map(fn($c) => $c->name, $this->pivotKeyColumns());

        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) =>
                $c->isEditable
                && !$c->isPrimaryKey
                && !$c->isCalculated
                && !in_array($c->name, $pivotKeys)
        ));
    }

    /**
     * Columnas de relaciones especiales (Pattern A: hasOne + where).
     * Estas relaciones apuntan a tablas pivote y representan los roles (deudor, acreedor, arrendador, etc.).
     *
     * @return ColumnMetadata[]
     */
    public function scopedRelations(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) => $c->sqlType === 'special_relation' && $c->scopeColumn !== null
        ));
    }

    /**
     * Columnas FK normales (no PK, no scoped relations).
     * Usadas para iterar en buildStoreFields, buildValidationRules, etc.
     *
     * @return ColumnMetadata[]
     */
    public function regularForeignKeys(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(ColumnMetadata $c) =>
                $c->isForeignKey
                && !$c->isPrimaryKey
                && $c->sqlType !== 'special_relation'
        ));
    }
}
