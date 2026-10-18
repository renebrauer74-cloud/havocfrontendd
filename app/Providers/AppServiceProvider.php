<?php

namespace App\Providers;

use App\Actions\BandManager\Users\CheckPermissionAction;
use App\Models\BandManager\CloudLink;
use App\Models\BandManager\User as BandManagerUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // Uploads über einen Cloud-Upload-Link: pro Besucher (IP) und Link – läuft vor dem Route-Model-Binding, daher der Token
        RateLimiter::for('cloud-link-uploads', function (Request $request): Limit {
            $link = $request->route('link');

            return Limit::perHour((int) config('band-manager.cloud.link_uploads_per_hour'))
                ->by($request->ip().'|'.($link instanceof CloudLink ? $link->token : (string) $link));
        });
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
