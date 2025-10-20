<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountPayableIdToFinancialMovementsTable extends Migration
{
    public function up()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('account_payable_id')->nullable()->after('user_id');
            $table->foreign('account_payable_id')
                  ->references('id')
                  ->on('accounts_payable')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->dropForeign(['account_payable_id']);
            $table->dropColumn('account_payable_id');
        });
    }
}
