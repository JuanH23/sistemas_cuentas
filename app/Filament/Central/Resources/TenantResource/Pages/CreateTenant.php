<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
    protected $domain;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar slug automáticamente con guiones
        $slug = Str::slug($data['name']); // Convierte "Papeleria Mila" → "papeleria-mila"
        $data['slug'] = $slug;
        
        // Si no se especificó un dominio, generarlo automáticamente
        $this->domain = $slug . '.sistema_cuentas_2.test';
        
        unset($data['domain']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $tenant = $this->record;

        try {
            Log::info("=== Starting tenant creation: {$tenant->id} ===");

            // 1. Crear el dominio
            Log::info("Step 1: Creating domain");
            $tenant->domains()->create([
                'domain' => $this->domain,
            ]);
            Log::info("✓ Domain created: {$this->domain}");

            // 2. Crear la base de datos
            Log::info("Step 2: Creating database");
            $this->createTenantDatabase($tenant);
            Log::info("✓ Database created");

            // 3. Ejecutar migraciones
            Log::info("Step 3: Running migrations");
            $this->migrateTenantDatabase($tenant);
            Log::info("✓ Migrations completed");

            // 4. Crear usuario admin
            Log::info("Step 4: Creating admin user");
            $this->createTenantAdmin($tenant);
            Log::info("✓ Admin user created");

            Log::info("=== Tenant creation completed successfully ===");

            Notification::make()
                ->success()
                ->title('✅ Tenant creado exitosamente')
                ->body("Usuario: admin@{$tenant->slug}.com / Contraseña: password<br>URL: http://{$this->domain}/app")
                ->persistent()
                ->send();

        } catch (\Exception $e) {
            Log::error("=== Error creating tenant {$tenant->id} ===");
            Log::error("Error: " . $e->getMessage());
            Log::error("File: " . $e->getFile() . ":" . $e->getLine());
            Log::error("Trace: " . $e->getTraceAsString());
            
            // Intentar limpiar el tenant creado
            try {
                $this->cleanupFailedTenant($tenant);
            } catch (\Exception $cleanupError) {
                Log::error("Cleanup error: " . $cleanupError->getMessage());
            }
            
            Notification::make()
                ->danger()
                ->title('❌ Error al crear tenant')
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
                
            throw $e;
        }
    }

    protected function createTenantDatabase($tenant): void
    {
        $databaseName = config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix');
        $centralConnection = config('tenancy.database.central_connection');
        
        Log::info("Creating database: {$databaseName}");
        
        DB::connection($centralConnection)->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        
        // Verificar que la BD se creó
        $databases = DB::connection($centralConnection)->select('SHOW DATABASES');
        $exists = collect($databases)->pluck('Database')->contains($databaseName);
        
        if (!$exists) {
            throw new \Exception("Database {$databaseName} was not created");
        }
        
        Log::info("✓ Database verified: {$databaseName}");
    }

    protected function migrateTenantDatabase($tenant): void
    {
        Log::info("Configuring tenant connection for migrations");
        
        try {
            $tenantDbName = config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix');
            
            Log::info("Target tenant database: {$tenantDbName}");
            
            // Configurar COMPLETAMENTE la conexión tenant
            $this->configureTenantConnection($tenantDbName);
            
            // Verificar la conexión
            $currentDb = DB::connection('tenant')->getDatabaseName();
            Log::info("✓ Connected to database: {$currentDb}");
            
            if ($currentDb !== $tenantDbName) {
                throw new \Exception("Failed to switch to tenant database. Connected to: {$currentDb}, expected: {$tenantDbName}");
            }
            
            // Ejecutar migraciones
            Log::info("Running migrations...");
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            
            $output = Artisan::output();
            Log::info("Migration output: " . $output);
            
            // Verificar tablas creadas
            $tables = DB::connection('tenant')->select('SHOW TABLES');
            $tableCount = count($tables);
            
            Log::info("✓ Tables created in tenant DB: {$tableCount}");
            
            if ($tableCount === 0) {
                throw new \Exception("No tables were created in tenant database");
            }
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                Log::info("  - Table: {$tableName}");
            }
            
        } catch (\Exception $e) {
            Log::error("Migration error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function createTenantAdmin($tenant): void
    {
        try {
            $tenantDbName = config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix');
            
            // Configurar la conexión
            $this->configureTenantConnection($tenantDbName);
            
            // Verificar BD
            $currentDb = DB::connection('tenant')->getDatabaseName();
            Log::info("Creating admin user in database: {$currentDb}");
            
            if ($currentDb !== $tenantDbName) {
                throw new \Exception("Wrong database for user creation: {$currentDb}, expected: {$tenantDbName}");
            }
            
            // Crear usuario en la conexión del tenant
            $user = \App\Models\User::on('tenant')->create([
                'name' => 'Admin ' . $tenant->name,
                'email' => "admin@{$tenant->slug}.com",
                'password' => bcrypt('password'),
            ]);
            
            Log::info("✓ Admin user created with ID: {$user->id} in database: {$currentDb}");
            
        } catch (\Exception $e) {
            Log::error("Failed to create admin: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Configurar completamente la conexión tenant
     */
    protected function configureTenantConnection(string $databaseName): void
    {
        // Obtener la configuración base de la conexión central
        $centralConfig = config('database.connections.central');
        
        // Configurar la conexión tenant con todos los parámetros necesarios
        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',
                'url' => env('DATABASE_URL'),
                'host' => $centralConfig['host'] ?? env('DB_HOST', '127.0.0.1'),
                'port' => $centralConfig['port'] ?? env('DB_PORT', '3306'),
                'database' => $databaseName,
                'username' => $centralConfig['username'] ?? env('DB_USERNAME', 'root'),
                'password' => $centralConfig['password'] ?? env('DB_PASSWORD', ''),
                'unix_socket' => $centralConfig['unix_socket'] ?? env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ]
        ]);
        
        // Purgar la conexión cached
        DB::purge('tenant');
        
        Log::info("✓ Tenant connection configured for database: {$databaseName}");
    }

    /**
     * Limpiar un tenant que falló al crearse
     */
    protected function cleanupFailedTenant($tenant): void
    {
        Log::info("Cleaning up failed tenant: {$tenant->id}");
        
        try {
            // Eliminar la base de datos si existe
            $databaseName = config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix');
            $centralConnection = config('tenancy.database.central_connection');
            
            DB::connection($centralConnection)->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            Log::info("✓ Database dropped: {$databaseName}");
            
            // Eliminar dominios
            $tenant->domains()->delete();
            Log::info("✓ Domains deleted");
            
            // Eliminar el tenant
            $tenant->delete();
            Log::info("✓ Tenant record deleted");
            
        } catch (\Exception $e) {
            Log::error("Cleanup failed: " . $e->getMessage());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}