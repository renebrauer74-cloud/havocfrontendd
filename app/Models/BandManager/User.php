<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Login für den Band-Manager: Agentur (Rolle "admin") oder Band (Rolle "artist").
 * Anlegen des ersten Admins per `php artisan band-manager:admin`.
 */
#[Table('bm_users')]
#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'theme', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ARTIST = 'artist';

    public const THEMES = ['light', 'dark'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Band, $this>
     */
    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class, 'bm_band_user');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return config('band-manager.roles.'.$this->role, $this->role);
    }

    /**
     * Recht laut config('band-manager.permissions') – unterstützt '*' und Wildcards wie 'gigs.*'.
     */
    public function hasPermission(string $permission): bool
    {
        foreach ((array) config('band-manager.permissions.'.$this->role, []) as $granted) {
            if ($granted === '*' || $granted === $permission) {
                return true;
            }

            if (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Startseite nach dem Login: Agentur → Dashboard, Bands → eigene Startseite.
     */
    public function homeUrl(): string
    {
        return $this->isAdmin() ? route('band-manager.admin.dashboard') : route('band-manager.home');
    }
}
