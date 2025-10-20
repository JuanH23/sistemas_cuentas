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
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable(); // Si deseas relacionarlo con una venta
            $table->string('client_name'); // Debido a que no cuentas con una tabla de clientes
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['pendiente', 'pagado', 'parcial', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
