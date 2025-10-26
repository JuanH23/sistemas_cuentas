<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnsureTenantConnectionBeforeAuth
{
    public function handle(Attempting $event): void
    {
        if (!tenancy()->initialized) {
            config(['database.default' => 'central']);
            DB::setDefaultConnection('central');
            // Log::info('🔵 No tenant - using central for auth');
            return;
        }

        // ✅ Reconexión agresiva
        config(['database.default' => 'tenant']);
        DB::purge('mysql');
        DB::purge('central');
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');

        Log::info('🔐 Auth attempt - forced tenant connection', [
            'default' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
            'tenant_initialized' => tenancy()->initialized,
        ]);
    }
}