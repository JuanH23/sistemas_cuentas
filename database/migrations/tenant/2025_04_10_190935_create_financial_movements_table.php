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
        Schema::create('financial_movements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['income', 'expense']);
            
            $table->foreignId('cash_flow_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('set null');
    
            $table->timestamps();
    
            $table->index(['date']);
            $table->index(['type']);
            $table->index(['category']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_movements');
    }
};
