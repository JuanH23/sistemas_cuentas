<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Campos del cliente (después de customer_name)
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->string('customer_email')->nullable()->after('customer_phone');
            $table->enum('customer_type', ['regular', 'wholesale', 'vip'])
                ->default('regular')
                ->after('customer_email');
            
            // Campos de montos (después de customer_type)
            $table->decimal('subtotal', 10, 2)->default(0)->after('customer_type');
            $table->decimal('discount', 10, 2)->default(0)->after('subtotal');
            // total_amount ya existe, no se agrega
            
            // Campos de pago (después de total_amount)
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'credit'])
                ->default('cash')
                ->after('total_amount');
            $table->enum('payment_status', ['paid', 'pending', 'partial'])
                ->default('paid')
                ->after('payment_method');
            
            // Observaciones
            $table->text('notes')->nullable()->after('payment_status');
            
            // Índices para búsquedas rápidas
            $table->index('customer_phone');
            $table->index('customer_type');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_phone']);
            $table->dropIndex(['customer_type']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['created_at']);
            
            $table->dropColumn([
                'customer_phone',
                'customer_email',
                'customer_type',
                'subtotal',
                'discount',
                'payment_method',
                'payment_status',
                'notes',
            ]);
        });
    }
};