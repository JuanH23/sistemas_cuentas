<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashClosuresTable extends Migration
{
    public function up()
    {
        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('income', 12, 2)->default(0);
            $table->decimal('expense', 12, 2)->default(0);
            $table->decimal('expected_balance', 12, 2);
            $table->decimal('real_balance', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->string('observation')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_closures');
    }
}
