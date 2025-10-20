<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si no hay tenant inicializado, continuar (dominio central)
        if (!tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenancy()->tenant;

        // Verificar si el tenant está suspendido
        if ($tenant->status === 'suspended') {
            // Redirigir a página de suspensión o mostrar mensaje
            return response()->view('tenant.suspended', [
                'tenant' => $tenant,
                'reason' => $tenant->suspension_reason ?? 'No especificada',
            ], 403);
        }

        // Verificar si está en período de prueba expirado (opcional)
        if ($tenant->status === 'trial' && $tenant->trial_ends_at && now()->greaterThan($tenant->trial_ends_at)) {
            return response()->view('tenant.trial-expired', [
                'tenant' => $tenant,
            ], 403);
        }

        return $next($request);
    }
}