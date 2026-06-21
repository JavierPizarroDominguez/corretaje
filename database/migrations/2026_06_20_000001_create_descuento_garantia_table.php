<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Descuento_Garantia', function (Blueprint $table) {
            $table->unsignedBigInteger('Cobro_Devolucion_id');
            $table->unsignedBigInteger('Cobro_Descuento_id');

            $table->primary(['Cobro_Devolucion_id', 'Cobro_Descuento_id']);
            $table->foreign('Cobro_Devolucion_id')->references('id')->on('Cobro')->cascadeOnDelete();
            $table->foreign('Cobro_Descuento_id')->references('id')->on('Cobro')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Descuento_Garantia');
    }
};
