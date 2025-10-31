<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parametro', function (Blueprint $table) {
            $table->id();

            // clave única para identificar el parámetro por código
            $table->string('clave')->unique();

            // valor genérico: puede ser número o texto corto
            $table->string('valor')->nullable();

            // descripción para mostrar en Filament
            $table->string('descripcion')->nullable();

            // activo / inactivo
            $table->boolean('estado')->default(true);

            $table->timestamps();
        });

        // 👇 sembramos el parámetro que vamos a usar en el afterCreate()
        DB::table('parametro')->insert([
            'clave'       => 'ganancia_mov_plataforma',
            'valor'       => '1500', // cambia aquí el valor por defecto
            'descripcion' => 'Valor fijo de ganancia que se genera por cada movimiento de plataforma',
            'estado'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametro');
    }
};
