<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceTenantConnection
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ SOLUCIÓN NUCLEAR: Si no hay tenant pero estamos en un subdominio, inicializarlo
        if (!tenancy()->initialized) {
            $domain = $request->getHost();
            
            // Verificar si NO es un dominio central
            $centralDomains = config('tenancy.central_domains', []);
            
            if (!in_array($domain, $centralDomains)) {
                // Intentar inicializar el tenant por dominio
                $domainModel = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->first();
                
                if ($domainModel && $domainModel->tenant) {
                    Log::warning('⚠️ Tenant not initialized, forcing initialization', [
                        'domain' => $domain,
                        'url' => $request->fullUrl(),
                        'is_livewire' => $request->header('X-Livewire') ? 'YES' : 'NO',
                    ]);
                    
                    tenancy()->initialize($domainModel->tenant);
                    
                    Log::info('✅ Tenant force-initialized successfully', [
                        'tenant_id' => $domainModel->tenant->getTenantKey(),
                        'database' => config('tenancy.database.prefix') . $domainModel->tenant->getTenantKey(),
                    ]);
                }
            }
        }
        
        // Si después de intentar inicializar TODAVÍA no hay tenant, usar central
        if (!tenancy()->initialized) {
            config(['database.default' => 'central']);
            DB::purge('tenant');
            DB::setDefaultConnection('central');
            
            Log::info('🔵 No tenant - using CENTRAL connection', [
                'url' => $request->fullUrl(),
                'domain' => $request->getHost(),
            ]);
            
            return $next($request);
        }

        // ✅ Forzar conexión tenant de forma MUY agresiva
        config(['database.default' => 'tenant']);
        
        // Purgar TODAS las conexiones
        DB::purge('mysql');
        DB::purge('central');
        DB::purge('tenant');
        
        // Reconectar
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
        
        Log::info('🟢 Tenant detected - using TENANT connection', [
            'tenant_id' => tenancy()->tenant->getTenantKey(),
            'default_connection' => config('database.default'),
            'tenant_db' => DB::connection()->getDatabaseName(),
            'url' => $request->fullUrl(),
            'is_livewire' => $request->header('X-Livewire') ? 'YES' : 'NO',
        ]);

        $response = $next($request);
        
        // ✅ IMPORTANTE: Volver a forzar después del request
        config(['database.default' => 'tenant']);
        DB::setDefaultConnection('tenant');
        
        Log::info('🔄 After request - maintaining tenant connection', [
            'connection' => DB::connection()->getDatabaseName(),
        ]);
        
        return $response;
    }
}