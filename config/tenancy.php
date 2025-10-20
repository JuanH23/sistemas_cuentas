<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;

return [
    'tenant_model' => \App\Models\Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * Dominios centrales (donde NO aplica tenancy)
     * Aquí va el panel de super admin
     */
    'central_domains' => [
        'localhost',       // Para desarrollo local
        '127.0.0.1',
        'sistema_cuentas_2.test',
        // 'admin.miapp.com',  // Para producción
        // 'miapp.com',        // Para producción
    ],

    /**
     * Bootstrappers: Se ejecutan cuando se detecta un tenant
     * Cambian la BD, cache, filesystem al del tenant
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,    
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],

    /**
     * Configuración de bases de datos
     */
    'database' => [
        // Conexión central (usuarios super admin, lista de tenants)
        'central_connection' => env('DB_CONNECTION', 'central'),

        /**
     * Conexión plantilla para tenants
         */
        'template_tenant_connection' => null,

        /**
         * Nombres de BD de tenants: tenant{id}
         * Ejemplo: tenant_abc123, tenant_def456
         */
        'prefix' => 'tenant',
        'suffix' => '',

        /**
         * Gestor de bases de datos por tenant
         */
        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    /**
     * Cache separado por tenant
     */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /**
     * Archivos separados por tenant
     */
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],

        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    /**
     * Features adicionales
     */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\TenantConfig::class,
        // Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],

    /**
     * Rutas de tenant habilitadas
     */
    'routes' => true,

    /**
     * Migraciones de tenant (BD separada)
     */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    /**
     * Seeders de tenant
     */
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];