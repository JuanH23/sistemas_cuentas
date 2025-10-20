<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantAppName
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized) {
            // Cambiar el nombre de la app al nombre del tenant
            $tenantName = tenant_name();
            
            if ($tenantName) {
                config(['app.name' => $tenantName]);
            }
        }

        return $next($request);
    }
}