<?php



namespace App\Filament\App\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
        
        // Forzar conexión del tenant ANTES de mostrar el formulario
        if (tenancy()->initialized) {
            $tenant = tenant();
            $expectedDb = config('tenancy.database.prefix') . $tenant->id;
            
            config([
                'database.connections.tenant.database' => $expectedDb,
                'database.default' => 'tenant',
            ]);
            
            DB::purge('tenant');
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');
            
            // Log::info('🔐 Login page: Forced tenant connection', [
            //     'tenant' => $tenant->name,
            //     'database' => DB::connection()->getDatabaseName(),
            // ]);
        }
    }
}