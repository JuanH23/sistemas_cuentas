<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->foreignId('platform_movement_id')->nullable()->constrained()->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->dropForeign(['platform_movement_id']);
            $table->dropColumn('platform_movement_id');
        });
    }
};
