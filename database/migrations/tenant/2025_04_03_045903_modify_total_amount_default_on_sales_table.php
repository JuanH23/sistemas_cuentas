<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_amount', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Remueve el valor por defecto (o vuelve al valor anterior)
            $table->decimal('total_amount', 10, 2)->default(null)->change();
        });
    }
};
