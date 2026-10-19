<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\GigStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use App\Services\BandManager\Pdf;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Gig anlegen/bearbeiten inkl. Day Sheet – Agentur und Bands (bei eigenen Shows) pflegen dieselben Felder.
 */
class GigRequest extends FormRequest
{
    /**
     * Freitextfelder des Day Sheets mit maximaler Länge.
     */
    private const DAYSHEET_TEXT = [
        'venue_street' => 150, 'venue_zip' => 10, 'venue_country' => 60, 'parking_info' => 2000,
        'schedule_notes' => 2000,
        'hotel_name' => 150, 'hotel_address' => 255, 'hotel_phone' => 50, 'hotel_notes' => 2000,
        'catering_notes' => 2000,
        'contact_venue_name' => 100, 'contact_venue_phone' => 50,
        'contact_tech_name' => 100, 'contact_tech_phone' => 50,
        'guestlist' => 150, 'wifi' => 150, 'backline_info' => 2000, 'merch_info' => 2000, 'info_notes' => 2000,
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $gig = $this->route('gig');

        return $gig instanceof Gig ? $this->user()->can('update', $gig) : $this->user()->can('gigs.create');
    }

    /**
     * Beträge im deutschen Format (1.500,50) in Zahlen umwandeln.
     */
    protected function prepareForValidation(): void
    {
        $fee = str_replace(' ', '', (string) $this->input('fee'));
        if (str_contains($fee, ',')) {
            $fee = str_replace(['.', ','], ['', '.'], $fee);
        }

        $this->merge([
            'fee' => $fee === '' ? null : $fee,
            'buyout_amount' => $this->filled('buyout_amount') ? str_replace(',', '.', (string) $this->input('buyout_amount')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $time = ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'];

        $rules = [
            'band_id' => ['required', 'integer', $this->accessibleBand()],
            'date' => ['required', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:190'],
            'venue' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', Rule::enum(GigStatusEnum::class)],
            'notes' => ['nullable', 'string', 'max:65000'],
            'set_length' => ['nullable', 'integer', 'min:1', 'max:600'],
            'catering' => ['nullable', Rule::in(array_keys(config('band-manager.gigs.catering_options')))],
            'buyout_amount' => [
                Rule::excludeIf(! in_array($this->input('catering'), ['buyout', 'both'], true)),
                'nullable', 'numeric', 'min:0', 'max:9999.99',
            ],
            'travelers' => ['nullable', 'array'],
            'travelers.*' => ['integer', 'distinct', Rule::exists('bm_team_members', 'id')->where('band_id', $this->integer('band_id'))],
            'location_sheets' => ['nullable', 'array', 'max:'.$this->freeLocationSheetSlots()],
            'location_sheets.*' => [
                'file',
                'mimetypes:'.implode(',', Gig::LOCATION_SHEET_TYPES),
                'max:'.$this->locationSheetMaxMb() * 1024,
                $this->readablePdf(),
            ],
        ];

        foreach (self::DAYSHEET_TEXT as $field => $max) {
            $rules[$field] = ['nullable', 'string', 'max:'.$max];
        }

        foreach (Gig::TIME_FIELDS as $field) {
            $rules[$field] = $time;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = $this->locationSheetMaxMb();

        return [
            'location_sheets.max' => 'Zu viele Location Sheets – erlaubt sind insgesamt '.config('band-manager.gigs.location_sheets.max_files').' pro Gig.',
            'location_sheets.*.mimetypes' => 'Location Sheets bitte als PDF, JPG oder PNG hochladen.',
            'location_sheets.*.max' => "Ein Location Sheet ist zu groß (max. {$maxMb} MB).",
            'location_sheets.*.uploaded' => "Ein Location Sheet ist zu groß (max. {$maxMb} MB) oder konnte nicht hochgeladen werden.",
            'location_sheets.*.file' => 'Ein Location Sheet konnte nicht hochgeladen werden.',
            'band_id.required' => 'Bitte eine Band wählen.',
            'band_id.integer' => 'Bitte eine Band wählen.',
            'date.*' => 'Ungültiges Datum.',
            'title.required' => 'Titel/Veranstaltung fehlt.',
            'fee.*' => 'Gage muss eine Zahl sein.',
            'status.*' => 'Ungültiger Status.',
            'buyout_amount.*' => 'Buy-out muss ein Betrag sein (z.B. 15,00).',
            'set_length.*' => 'Spielzeit muss zwischen 1 und 600 Minuten liegen.',
            'travelers.*' => 'Bitte nur Personen aus Band & Team dieser Band wählen.',
            'regex' => ':attribute muss eine Uhrzeit sein (HH:MM).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'time_get_in' => 'Get In', 'time_soundcheck' => 'Soundcheck', 'time_dinner' => 'Dinner',
            'time_doors' => 'Einlass', 'time_show' => 'Showtime', 'time_curfew' => 'Curfew',
            'hotel_checkin' => 'Check-in', 'hotel_checkout' => 'Check-out',
        ];
    }

    /**
     * Felder für das Model – Buy-out nur bei passender Verpflegung.
     *
     * @return array<string, mixed>
     */
    public function gigAttributes(): array
    {
        $attributes = $this->safe()->except([...Gig::TIME_FIELDS, 'travelers', 'location_sheets']) + ['buyout_amount' => null];

        foreach (Gig::TIME_FIELDS as $field) {
            $attributes[$field] = $this->validated($field);
        }

        return $attributes;
    }

    /**
     * Ausgewählte Reisegruppe (IDs aus Band & Team der gewählten Band).
     *
     * @return list<int>
     */
    public function travelerIds(): array
    {
        return array_values(array_map('intval', $this->validated('travelers') ?? []));
    }

    /**
     * Neu hochgeladene Location Sheets.
     *
     * @return list<UploadedFile>
     */
    public function locationSheets(): array
    {
        return array_values($this->validated('location_sheets') ?? []);
    }

    /**
     * Wie viele Location Sheets der Gig noch aufnehmen kann.
     */
    private function freeLocationSheetSlots(): int
    {
        $gig = $this->route('gig');
        $existing = $gig instanceof Gig ? $gig->getMedia(MediaCollectionEnum::LocationSheets->value)->count() : 0;

        return max(0, (int) config('band-manager.gigs.location_sheets.max_files') - $existing);
    }

    /**
     * PDFs müssen sich ins Day Sheet übernehmen lassen – verschlüsselte oder beschädigte nicht.
     */
    private function readablePdf(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof UploadedFile || $value->getMimeType() !== 'application/pdf') {
                return;
            }

            if (app(Pdf::class)->pageCount((string) $value->get()) === null) {
                $fail('„'.$value->getClientOriginalName().'“ lässt sich nicht ins Day Sheet übernehmen (z. B. passwortgeschützt). Bitte die Datei neu als PDF speichern oder als Bild hochladen.');
            }
        };
    }

    private function locationSheetMaxMb(): int
    {
        return (int) config('band-manager.gigs.location_sheets.max_size_mb');
    }

    /**
     * Nur Bands, auf die der Benutzer Zugriff hat.
     */
    private function accessibleBand(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $band = Band::query()->find($value);

            if ($band === null || ! $this->user()->can('access', $band)) {
                $fail('Bitte eine Band wählen.');
            }
        };
    }
}
