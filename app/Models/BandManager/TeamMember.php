<?php

namespace App\Models\BandManager;

use App\Services\BandManager\Images;
use Carbon\CarbonImmutable;
use Database\Factories\BandManager\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Bandmitglied oder Crew mit Steckbrief, Foto und Stammdaten.
 * Fotos liegen auf dem Disk "band-manager" unter team/{band_id}/.
 */
#[Table('bm_team_members')]
#[Fillable([
    'band_id', 'type', 'first_name', 'last_name', 'stage_name', 'position', 'crew_role', 'crew_role_other', 'bio', 'photo',
    'birth_date', 'nationality', 'email', 'phone', 'street', 'zip', 'city', 'country',
    'shirt_size', 'diet', 'driver_license', 'emergency_contact', 'created_by',
])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    public const TYPE_MEMBER = 'member';

    public const TYPE_CREW = 'crew';

    public const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    protected static function booted(): void
    {
        static::deleted(fn (TeamMember $member) => $member->deletePhotoFile());
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function isCrew(): bool
    {
        return $this->type === self::TYPE_CREW;
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    /**
     * Crew: Aufgabe (bei "Sonstiges" der eigene Text) plus Zusatz – Band: Instrument / Rolle.
     */
    public function roleLabel(): string
    {
        if (! $this->isCrew()) {
            return $this->position ?: 'Bandmitglied';
        }

        $role = $this->crew_role === 'other' && $this->crew_role_other
            ? $this->crew_role_other
            : config('band-manager.team.crew_roles.'.$this->crew_role, 'Team');

        return $this->position ? $role.' · '.$this->position : $role;
    }

    public function age(): ?int
    {
        return $this->birth_date ? (int) CarbonImmutable::parse($this->birth_date)->diffInYears(CarbonImmutable::today()) : null;
    }

    /**
     * Stammdaten für die Profilseite – nur ausgefüllte Felder.
     *
     * @return list<array{0: string, 1: string, 2: ?string}> [Label, Wert, Link]
     */
    public function facts(): array
    {
        $address = collect([$this->street, trim($this->zip.' '.$this->city), $this->country])->filter()->join("\n");
        $birth = $this->birth_date ? CarbonImmutable::parse($this->birth_date)->format('d.m.Y').' ('.$this->age().' Jahre)' : null;

        $rows = [
            ['Geburtsdatum', $birth, null],
            ['Nationalität', $this->nationality, null],
            ['E-Mail', $this->email, $this->email ? 'mailto:'.$this->email : null],
            ['Telefon', $this->phone, $this->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $this->phone) : null],
            ['Adresse', $address, null],
            ['T-Shirt-Größe', $this->shirt_size, null],
            ['Ernährung / Allergien', $this->diet, null],
            ['Führerschein', $this->driver_license, null],
            ['Notfallkontakt', $this->emergency_contact, null],
        ];

        return array_values(array_filter($rows, fn (array $row): bool => filled($row[1])));
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? route('band-manager.team.photo', ['member' => $this, 'v' => substr($this->photo, 0, 8)]) : null;
    }

    public function photoPath(): ?string
    {
        return $this->photo ? 'team/'.$this->band_id.'/'.basename($this->photo) : null;
    }

    /**
     * Foto speichern (auf 800 px verkleinert, ohne GD im Original). Liefert den Dateinamen.
     */
    public static function storePhoto(UploadedFile $file, int $bandId): string
    {
        $disk = Storage::disk('band-manager');
        $name = bin2hex(random_bytes(16));
        $mime = (string) $file->getMimeType();

        if (app(Images::class)->resizeToJpeg($file->getRealPath(), $mime, $disk->path("team/{$bandId}/{$name}.jpg"), (int) config('band-manager.team.photo_px', 800))) {
            return "{$name}.jpg";
        }

        $fileName = $name.'.'.(self::PHOTO_TYPES[$mime] ?? 'jpg');
        $disk->putFileAs("team/{$bandId}", $file, $fileName);

        return $fileName;
    }

    public function deletePhotoFile(): void
    {
        if ($this->photoPath() !== null) {
            Storage::disk('band-manager')->delete($this->photoPath());
        }
    }
}
