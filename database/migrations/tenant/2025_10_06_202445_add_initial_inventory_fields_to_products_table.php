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
        Schema::table('products', function (Blueprint $table) {
            // Campo para marcar si es inventario inicial
            $table->boolean('is_initial_inventory')
                ->default(false)
                ->after('financial_movement_id')
                ->comment('Indica si el producto es inventario inicial (no afecta flujo de caja)');
            
            // Campo para fecha de adquisición original (solo para inventario inicial)
            $table->date('acquisition_date')
                ->nullable()
                ->after('is_initial_inventory')
                ->comment('Fecha original de compra del inventario inicial');
            
            // Índice para mejorar consultas
            $table->index('is_initial_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar índice primero
            $table->dropIndex(['is_initial_inventory']);
            
            // Eliminar columnas
            $table->dropColumn([
                'is_initial_inventory',
                'acquisition_date',
            ]);
        });
    }
};