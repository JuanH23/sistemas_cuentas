<?php
// app/Helpers/helpers.php

use App\Models\Tenant;

if (!function_exists('tenant_info')) {
    /**
     * Obtener información del tenant actual desde la BD central
     * 
     * @return object|null
     */
    function tenant_info()
    {
        if (!tenancy()->initialized) {
            return null;
        }

        $tenantId = tenancy()->tenant->getTenantKey();

        // Sin caché - consulta directa
        return Tenant::find($tenantId);
    }
}

if (!function_exists('tenant_name')) {
    /**
     * Obtener el nombre del tenant actual
     * 
     * @return string|null
     */
    function tenant_name()
    {
        return tenant_info()?->name;
    }
}

if (!function_exists('tenant_email')) {
    /**
     * Obtener el email del tenant actual
     * 
     * @return string|null
     */
    function tenant_email()
    {
        return tenant_info()?->email;
    }
}

if (!function_exists('tenant_phone')) {
    /**
     * Obtener el teléfono del tenant actual
     * 
     * @return string|null
     */
    function tenant_phone()
    {
        return tenant_info()?->phone;
    }
}