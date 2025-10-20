<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('platform_movements', function (Blueprint $table) {
            // 1. Eliminar los enums antiguos
            $table->dropColumn(['platform', 'type']);

            // 2. Agregar nuevas llaves foráneas
            $table->foreignId('platform_id')->nullable()->after('date')->constrained()->onDelete('cascade');
            $table->foreignId('platform_movement_type_id')->nullable()->after('platform_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('platform_movements', function (Blueprint $table) {
            // Eliminar claves foráneas
            $table->dropForeign(['platform_id']);
            $table->dropForeign(['platform_movement_type_id']);

            $table->dropColumn(['platform_id', 'platform_movement_type_id']);

            // Restaurar los enums
            $table->enum('platform', ['Bancolombia', 'Nequi', 'MoviRed', 'Daviplata'])->after('date');
            $table->enum('type', ['recarga', 'pago_factura', 'transferencia', 'retiro'])->after('platform');
        });
    }
};
