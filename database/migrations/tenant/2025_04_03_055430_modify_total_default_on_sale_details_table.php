<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTotalDefaultOnSaleDetailsTable extends Migration
{
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->default(null)->change();
        });
    }
}
