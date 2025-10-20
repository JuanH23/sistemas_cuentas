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
        Schema::table('platform_movements', function (Blueprint $table) {
            $table->string('anexo')->nullable()->after('description');
        });
    }
    
    public function down()
    {
        Schema::table('platform_movements', function (Blueprint $table) {
            $table->dropColumn('anexo');
        });
    }
    
};
