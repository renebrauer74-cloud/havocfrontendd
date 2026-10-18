<?php

namespace App\Actions\BandManager;

use App\Models\BandManager\ActivityLog;
use App\Services\BandManager\Impersonation;
use Illuminate\Support\Facades\Auth;

/**
 * Protokolliert eine Aktion des eingeloggten Benutzers – beim „Einloggen als“ inkl. des Admins dahinter.
 */
class RecordActivityAction
{
    public function __construct(private Impersonation $impersonation) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(string $action, array $context = []): ActivityLog
    {
        if ($this->impersonation->isActive()) {
            $context['impersonator_id'] = $this->impersonation->impersonatorId();
        }

        return ActivityLog::query()->create([
            'user_id' => Auth::guard('band-manager')->id(),
            'action' => $action,
            'context' => $context,
        ]);
    }
}
