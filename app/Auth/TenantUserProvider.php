<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantUserProvider extends EloquentUserProvider
{
    /**
     * Forzar conexión tenant de forma agresiva
     */
    protected function ensureTenantConnection(): void
    {
        if (!tenancy()->initialized) {
            Log::warning('⚠️ No tenant initialized - using central');
            config(['database.default' => 'central']);
            DB::purge('mysql');
            DB::purge('tenant');
            DB::setDefaultConnection('central');
            return;
        }

        // ✅ FORZAR la reconexión tenant
        config(['database.default' => 'tenant']);
        
        // Purgar TODAS las conexiones para forzar reconexión
        DB::purge('mysql');
        DB::purge('central');
        DB::purge('tenant');
        
        // Reconectar y establecer como default
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
        
        Log::info('✅ TenantUserProvider: Forced tenant connection', [
            'default' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
        ]);
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && isset($credentials['password']))) {
            return null;
        }

        // ✅ SIEMPRE forzar antes de cualquier query
        $this->ensureTenantConnection();
        
        Log::info('🔐 Retrieving user by credentials', [
            'email' => $credentials['email'] ?? 'N/A',
            'connection' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
        ]);

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (!str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        $user = $query->first();
        
        Log::info('🔍 User search result', [
            'found' => $user ? 'YES' : 'NO',
            'user_id' => $user?->id,
            'database' => DB::connection()->getDatabaseName(),
        ]);

        return $user;
    }

    public function retrieveById($identifier)
    {
        $this->ensureTenantConnection();
        
        Log::info('🔍 Retrieving user by ID', [
            'id' => $identifier,
            'connection' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
        ]);

        return parent::retrieveById($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        $this->ensureTenantConnection();
        return parent::retrieveByToken($identifier, $token);
    }

    public function createModel()
    {
        $this->ensureTenantConnection();
        
        $class = '\\'.ltrim($this->model, '\\');
        return new $class;
    }

    public function newModelQuery($model = null)
    {
        $this->ensureTenantConnection();
        return parent::newModelQuery($model);
    }
}