<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsPayableTable extends Migration
{
    public function up()
    {
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            // Si manejas proveedores con una tabla separada, usarías provider_id y la clave foránea.
            // Aquí usaremos un campo de texto para el nombre del proveedor.
            $table->string('provider_name');
            $table->string('invoice_number')->nullable()->unique(); // Puede ser nulo si no se emite factura.
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['pendiente', 'parcial', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts_payable');
    }
}
