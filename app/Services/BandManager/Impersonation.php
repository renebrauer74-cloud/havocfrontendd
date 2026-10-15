<?php

namespace App\Services\BandManager;

use App\Models\BandManager\User;
use Illuminate\Support\Facades\Auth;

/**
 * "Einloggen als": Ein Admin sieht den Band-Manager mit den Augen eines Künstlers.
 * Der ursprüngliche Admin wird in der Session gemerkt, damit er zurückwechseln kann.
 */
class Impersonation
{
    private const SESSION_KEY = 'band_manager.impersonator_id';

    public function start(User $admin, User $target): void
    {
        session()->put(self::SESSION_KEY, $admin->id);

        Auth::guard('band-manager')->login($target);
    }

    /**
     * Zurück ins Admin-Konto. Liefert den Admin oder null, wenn gerade niemand übernommen ist.
     */
    public function stop(): ?User
    {
        $admin = User::query()->find(session()->pull(self::SESSION_KEY));

        if ($admin !== null) {
            Auth::guard('band-manager')->login($admin);
        }

        return $admin;
    }

    public function isActive(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function impersonatorId(): ?int
    {
        return session()->get(self::SESSION_KEY);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
