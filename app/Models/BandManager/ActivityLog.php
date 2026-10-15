<?php

namespace App\Models\BandManager;

use App\Services\BandManager\Impersonation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Protokoll für "Letzte Aktivitäten" im Dashboard.
 */
#[Table('bm_activity_log')]
#[Fillable(['user_id', 'action', 'context'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Aktion des eingeloggten Benutzers protokollieren – beim "Einloggen als" inkl. des Admins dahinter.
     *
     * @param  array<string, mixed>  $context
     */
    public static function record(string $action, array $context = []): self
    {
        $impersonation = app(Impersonation::class);

        if ($impersonation->isActive()) {
            $context['impersonator_id'] = $impersonation->impersonatorId();
        }

        return static::query()->create([
            'user_id' => Auth::guard('band-manager')->id(),
            'action' => $action,
            'context' => $context,
        ]);
    }
}
