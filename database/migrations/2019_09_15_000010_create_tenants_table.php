<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            
            // Información básica
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Estado
            $table->enum('status', ['active', 'suspended', 'trial'])->default('active');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Límites
            $table->integer('max_users')->default(10);
            
            // Metadata
            $table->json('data')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};