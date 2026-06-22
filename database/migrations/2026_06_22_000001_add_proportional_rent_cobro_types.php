<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `Cobro` MODIFY `tipo` ENUM('Ingreso Renta Arrendatario','Egreso Renta Arrendador','Ingreso Proporcional Renta Arrendatario','Egreso Proporcional Renta Arrendador','Comision inicial arrendador','Comision inicial arrendatario','Comision Mensual','Ingreso Garantía Arrendatario','Egreso Garantía Arrendador','Devolución Garantía Arrendatario','Aseo Final','Luz','Agua','Gas','Gastos comunes','Reparación','Extra','Devolución') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `Cobro` MODIFY `tipo` ENUM('Ingreso Renta Arrendatario','Egreso Renta Arrendador','Comision inicial arrendador','Comision inicial arrendatario','Comision Mensual','Ingreso Garantía Arrendatario','Egreso Garantía Arrendador','Devolución Garantía Arrendatario','Aseo Final','Luz','Agua','Gas','Gastos comunes','Reparación','Extra','Devolución') NOT NULL");
    }
};
