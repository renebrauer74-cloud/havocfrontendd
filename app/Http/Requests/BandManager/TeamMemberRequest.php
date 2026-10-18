<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\CrewRoleEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;
use App\Rules\MaxMegapixelsRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Band- oder Teammitglied anlegen/bearbeiten. Das Recht team.edit prüft die Route.
 */
class TeamMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access', $this->band());
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCrew = $this->type() === TeamMember::TYPE_CREW;

        return [
            'type' => ['nullable', Rule::in([TeamMember::TYPE_MEMBER, TeamMember::TYPE_CREW])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'stage_name' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:150'],
            'crew_role' => $isCrew ? ['required', Rule::enum(CrewRoleEnum::class)] : ['exclude'],
            'crew_role_other' => $isCrew && $this->input('crew_role') === CrewRoleEnum::Other->value ? ['required', 'string', 'max:100'] : ['exclude'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:190'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'shirt_size' => ['nullable', Rule::in(config('band-manager.team.shirt_sizes'))],
            'diet' => ['nullable', 'string', 'max:255'],
            'driver_license' => ['nullable', 'string', 'max:50'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'nullable', 'file', 'mimetypes:'.implode(',', array_keys(TeamMember::PHOTO_TYPES)), 'image', 'max:'.$this->maxPhotoMb() * 1024,
                new MaxMegapixelsRule((int) config('band-manager.team.max_megapixels')),
            ],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = $this->maxPhotoMb();

        return [
            'first_name.required' => 'Vorname fehlt.',
            'crew_role.*' => 'Bitte die Aufgabe im Team wählen.',
            'crew_role_other.required' => 'Bitte bei „Sonstiges“ eintragen, welche Aufgabe die Person hat.',
            'email.email' => 'Ungültige E-Mail-Adresse.',
            'birth_date.*' => 'Ungültiges Geburtsdatum.',
            'photo.max' => "Das Foto ist zu groß (max. {$maxMb} MB).",
            'photo.uploaded' => "Das Foto ist zu groß (max. {$maxMb} MB) oder konnte nicht hochgeladen werden.",
            'photo.mimetypes' => 'Als Foto sind nur JPG-, PNG- oder WebP-Bilder erlaubt.',
            'photo.image' => 'Als Foto sind nur JPG-, PNG- oder WebP-Bilder erlaubt.',
        ];
    }

    /**
     * Felder für das Model (ohne Foto – das speichert der Controller).
     *
     * @return array<string, mixed>
     */
    public function personAttributes(): array
    {
        $attributes = $this->safe()->except(['type', 'photo', 'remove_photo']);

        $attributes['crew_role'] ??= null;
        $attributes['crew_role_other'] ??= null;

        return $attributes;
    }

    /**
     * Neu: aus dem Formular, beim Bearbeiten bleibt die Art der Person gleich.
     */
    public function type(): string
    {
        $member = $this->route('member');

        if ($member instanceof TeamMember) {
            return $member->type;
        }

        return $this->input('type') === TeamMember::TYPE_CREW ? TeamMember::TYPE_CREW : TeamMember::TYPE_MEMBER;
    }

    public function band(): Band
    {
        $member = $this->route('member');

        return $member instanceof TeamMember ? $member->band : $this->route('band');
    }

    private function maxPhotoMb(): int
    {
        return (int) config('band-manager.team.max_photo_mb');
    }
}
