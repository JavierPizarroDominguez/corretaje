<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Deduplicate propiedad direccion:
     * - Find properties with matching LOWER(direccion), keeping the one with lowest id as canonical
     * - Reassign FK references on unidad, cobro, servicio to canonical id
     * - Delete duplicate property rows
     * - Add UNIQUE index on LOWER(direccion)
     *
     * Wrapped in a transaction for atomicity.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::transaction(function () {
            // Find all duplicate direcciones (case-insensitive), grouped by lower direccion
            $duplicates = DB::select("
                SELECT MIN(id) as canonical_id, LOWER(direccion) as direccion_lower, COUNT(*) as cnt
                FROM propiedad
                GROUP BY LOWER(direccion)
                HAVING COUNT(*) > 1
            ");

            foreach ($duplicates as $dup) {
                // Get all duplicate ids (excluding canonical)
                $duplicateIds = DB::select(
                    "SELECT id FROM propiedad WHERE LOWER(direccion) = ? AND id != ?",
                    [$dup->direccion_lower, $dup->canonical_id]
                );

                $dupIds = array_column($duplicateIds, 'id');

                if (empty($dupIds)) {
                    continue;
                }

                // Reassign unidad references
                DB::table('unidad')
                    ->whereIn('Propiedad_id', $dupIds)
                    ->update(['Propiedad_id' => $dup->canonical_id]);

                // Reassign cobro references
                DB::table('cobro')
                    ->whereIn('Propiedad_id', $dupIds)
                    ->update(['Propiedad_id' => $dup->canonical_id]);

                // Reassign servicio references
                DB::table('servicio')
                    ->whereIn('Propiedad_id', $dupIds)
                    ->update(['Propiedad_id' => $dup->canonical_id]);

                // Delete duplicate properties
                DB::table('propiedad')
                    ->whereIn('id', $dupIds)
                    ->delete();
            }

            // Add unique index on lowercased direccion
            DB::statement("
                CREATE UNIQUE INDEX propiedad_direccion_unique_lower
                ON propiedad (LOWER(direccion))
            ");
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse: drop the unique index. Duplicate data is not recreated.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::transaction(function () {
            DB::statement("DROP INDEX IF EXISTS propiedad_direccion_unique_lower");
        });

        Schema::enableForeignKeyConstraints();
    }
};
