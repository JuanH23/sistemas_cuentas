<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            
            // CAMBIO: Usar string en lugar de bigInteger para soportar UUIDs
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();  // ← CAMBIO AQUÍ
            $table->index(['subject_type', 'subject_id'], 'subject');
            
            $table->string('event')->nullable();
            
            // CAMBIO: Usar string para causer_id también
            $table->string('causer_type')->nullable();
            $table->string('causer_id')->nullable();  // ← CAMBIO AQUÍ
            $table->index(['causer_type', 'causer_id'], 'causer');
            
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->drop(config('activitylog.table_name'));
    }
};