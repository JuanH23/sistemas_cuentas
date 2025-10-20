<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_movements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('platform', ['Bancolombia', 'Nequi', 'MoviRed', 'Daviplata']);
            $table->enum('type', ['recarga', 'pago_factura', 'transferencia', 'retiro']);
            $table->string('reference')->nullable();
            $table->string('operator')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cash_flow_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_movements');
    }
};
