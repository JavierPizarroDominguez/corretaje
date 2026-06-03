<?php

namespace App\Generator\Introspection;

use Illuminate\Support\Facades\DB;

class ConstraintParser
{
    private string $database;

    public function __construct()
    {
        $this->database = config('database.connections.mysql.database');
    }

    /**
     * Devuelve los CHECK constraints de una tabla.
     * Clave: nombre del constraint. Valor: cláusula SQL raw.
     *
     * Ejemplo para 'contrato':
     * [
     *   'chk_comision_mensual_contrato' => '`renta` >= `comision_mensual`'
     * ]
     */
    public function getCheckConstraints(string $table): array
    {
        // MySQL 8.0.16+ expone CHECK constraints en information_schema
        $rows = DB::select("
            SELECT
                cc.CONSTRAINT_NAME,
                cc.CHECK_CLAUSE
            FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.CHECK_CONSTRAINTS cc
                ON  cc.CONSTRAINT_NAME   = tc.CONSTRAINT_NAME
                AND cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
            WHERE tc.TABLE_SCHEMA    = ?
              AND tc.TABLE_NAME      = ?
              AND tc.CONSTRAINT_TYPE = 'CHECK'
        ", [$this->database, $table]);

        $result = [];

        foreach ($rows as $row) {
            $result[$row->CONSTRAINT_NAME] = $row->CHECK_CLAUSE;
        }

        return $result;
    }

    /**
     * Genera un mensaje legible automático desde el nombre del constraint.
     * Se usa como fallback cuando config/generator.php no define el mensaje.
     *
     * Ejemplo: 'chk_comision_mensual_contrato' → 'Error en comision mensual contrato.'
     */
    public function autoMessage(string $constraintName): string
    {
        $name = preg_replace('/^chk_/', '', $constraintName);
        return ucfirst(str_replace('_', ' ', $name)) . '.';
    }
}
