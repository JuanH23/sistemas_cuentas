<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use BezhanSalleh\FilamentShield\FilamentShield;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use App\Listeners\EnsureTenantConnectionBeforeAuth;
use Illuminate\Support\Facades\Auth;
use App\Auth\TenantUserProvider;
use Illuminate\Auth\Events\Attempting;

class AppServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Activity::class => ActivityPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurePolicies();

        $this->configureDB();

        $this->configureModels();

        $this->configureFilament();
        
        Auth::provider('tenant-eloquent', function ($app, array $config) {
            return new TenantUserProvider($app['hash'], $config['model']);
        });
        \Event::listen(Attempting::class, EnsureTenantConnectionBeforeAuth::class);
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                if (tenancy()->initialized) {
                    $expectedDb = config('tenancy.database.prefix') . tenancy()->tenant->getTenantKey();
                    $actualDb = $query->connectionName;
                    
                    if ($query->connectionName === 'central' || 
                        DB::connection($query->connectionName)->getDatabaseName() !== $expectedDb) {
                        
                        \Log::warning('⚠️ WRONG CONNECTION DETECTED', [
                            'query' => $query->sql,
                            'connection' => $query->connectionName,
                            'actual_db' => DB::connection($query->connectionName)->getDatabaseName(),
                            'expected_db' => $expectedDb,
                        ]);
                    }
                }
            });
        }
    }

    private function configurePolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    private function configureDB(): void
    {
        DB::prohibitDestructiveCommands($this->app->environment('production'));
    }

    private function configureModels(): void
    {
        Model::preventAccessingMissingAttributes();

        Model::unguard();
    }

    private function configureFilament(): void
    {
        FilamentShield::prohibitDestructiveCommands($this->app->isProduction());
    }
}
