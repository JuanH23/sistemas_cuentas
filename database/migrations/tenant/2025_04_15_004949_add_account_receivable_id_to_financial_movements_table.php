<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountReceivableIdToFinancialMovementsTable extends Migration
{
    public function up()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('account_receivable_id')->nullable()->after('user_id');
            // Opcional: definir la clave foránea para relacionar con accounts_receivable
            $table->foreign('account_receivable_id')
                  ->references('id')
                  ->on('accounts_receivable')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->dropForeign(['account_receivable_id']);
            $table->dropColumn('account_receivable_id');
        });
    }
}
