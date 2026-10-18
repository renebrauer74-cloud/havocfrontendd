<?php

namespace App\Providers;

use App\Actions\BandManager\Users\CheckPermissionAction;
use App\Models\BandManager\User as BandManagerUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineBandManagerGates();
    }

    /**
     * Rechte des Band-Managers: Jedes Recht aus config('band-manager.permissions') ist ein Gate
     * (@can('gigs.view')). Admins ('*') dürfen alles – auch alle Policies.
     */
    private function defineBandManagerGates(): void
    {
        Gate::before(function (Authenticatable $user, string $ability): ?bool {
            return $user instanceof BandManagerUser && app(CheckPermissionAction::class)->execute($user, $ability) ? true : null;
        });

        Gate::define('band-manager.admin', fn (BandManagerUser $user): bool => $user->isAdmin());
    }
}
