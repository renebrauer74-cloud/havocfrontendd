{{-- Day-Sheet-Felder im Gig-Formular – Agentur und Bands (bei eigenen Shows) --}}
@php
    $heading = fn (string $icon, string $title, string $hint = ''): string => '<div class="col-12 border-top pt-3 mt-3"><h2 class="h5 mb-0"><i class="bi bi-'.e($icon).' me-1"></i>'.e($title).'</h2>'
        .($hint !== '' ? '<div class="form-text mt-0">'.e($hint).'</div>' : '').'</div>';
    $catering = old('catering', $gig->catering);
@endphp

<div class="col-12 mt-4">
    <div class="alert alert-info mb-0 small">
        <strong>Day Sheet:</strong> Aus diesen Infos entsteht das Day Sheet (PDF). Leere Felder erscheinen im PDF nicht.
    </div>
</div>

{!! $heading('geo-alt', 'Location & Anfahrt', 'Der Ort kommt aus dem Feld „Stadt“ oben.') !!}
<x-band-manager.field name="venue_street" label="Straße & Hausnummer" :value="$gig->venue_street" />
<x-band-manager.field name="venue_zip" label="PLZ" :value="$gig->venue_zip" col="col-4 col-md-2" :max="10" />
<x-band-manager.field name="venue_country" label="Land" :value="$gig->venue_country" col="col-8 col-md-4" placeholder="Deutschland" :max="60" />
<x-band-manager.textarea name="parking_info" label="Anfahrt / Parken / Ladezone" :value="$gig->parking_info"
                         placeholder="z.B. Ladezone im Hinterhof, Sprinter-Parkplatz am Bühneneingang …" />

{!! $heading('clock', 'Zeitplan') !!}
<x-band-manager.field name="time_get_in" label="Get In" :value="$gig->time('time_get_in')" col="col-6 col-md-3" type="time" />
<x-band-manager.field name="time_soundcheck" label="Soundcheck" :value="$gig->time('time_soundcheck')" col="col-6 col-md-3" type="time" />
<x-band-manager.field name="time_dinner" label="Dinner" :value="$gig->time('time_dinner')" col="col-6 col-md-3" type="time" />
<x-band-manager.field name="time_doors" label="Einlass (Doors)" :value="$gig->time('time_doors')" col="col-6 col-md-3" type="time" />
<x-band-manager.field name="time_show" label="Showtime" :value="$gig->time('time_show')" col="col-6 col-md-3" type="time" />
<div class="col-6 col-md-3">
    <label class="form-label" for="set_length">Spielzeit (Min.)</label>
    <input type="number" name="set_length" id="set_length" class="form-control" min="1" max="600" placeholder="z.B. 60" value="{{ old('set_length', $gig->set_length) }}">
</div>
<x-band-manager.field name="time_curfew" label="Curfew" :value="$gig->time('time_curfew')" col="col-6 col-md-3" type="time" />
<x-band-manager.textarea name="schedule_notes" label="Weitere Zeiten" :value="$gig->schedule_notes"
                         placeholder="z.B. Support 20:00, Changeover 15 Min., Load-Out bis 00:30, Abfahrt Hotel 10:00 …" />

{!! $heading('building', 'Unterkunft') !!}
<x-band-manager.field name="hotel_name" label="Hotel / Unterkunft" :value="$gig->hotel_name" />
<x-band-manager.field name="hotel_phone" label="Telefon" :value="$gig->hotel_phone" type="tel" :max="50" />
<x-band-manager.field name="hotel_address" label="Adresse" :value="$gig->hotel_address" col="col-12" placeholder="Straße, PLZ Ort" :max="255" />
<x-band-manager.field name="hotel_checkin" label="Check-in ab" :value="$gig->time('hotel_checkin')" col="col-6 col-md-3" type="time" />
<x-band-manager.field name="hotel_checkout" label="Check-out bis" :value="$gig->time('hotel_checkout')" col="col-6 col-md-3" type="time" />
<x-band-manager.textarea name="hotel_notes" label="Hinweise" :value="$gig->hotel_notes"
                         placeholder="z.B. Buchungsnr., 3 DZ + 1 EZ, Frühstück inklusive, Parkplatz am Hotel …" />

{!! $heading('cup-hot', 'Verpflegung') !!}
<div class="col-md-6">
    <label class="form-label" for="dsCatering">Verpflegung</label>
    <select name="catering" id="dsCatering" class="form-select">
        <option value="">– noch offen –</option>
        @foreach ($cateringOptions as $key => $label)
            <option value="{{ $key }}" @selected($catering === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div @class(['col-md-6', 'd-none' => ! in_array($catering, ['buyout', 'both'], true)]) id="dsBuyout">
    <label class="form-label" for="buyout_amount">Buy-out pro Person (€)</label>
    <input name="buyout_amount" id="buyout_amount" class="form-control" inputmode="decimal" placeholder="z.B. 15,00"
           value="{{ old('buyout_amount', $gig->buyout_amount !== null ? number_format((float) $gig->buyout_amount, 2, ',', '') : '') }}">
</div>
<x-band-manager.textarea name="catering_notes" label="Hinweise" :value="$gig->catering_notes"
                         placeholder="z.B. Getränke frei, warmes Essen um 18:30, 2× vegan …" />

{!! $heading('telephone', 'Ansprechpartner vor Ort') !!}
<x-band-manager.field name="contact_venue_name" label="Location – Name" :value="$gig->contact_venue_name" :max="100" />
<x-band-manager.field name="contact_venue_phone" label="Location – Telefon" :value="$gig->contact_venue_phone" type="tel" :max="50" />
<x-band-manager.field name="contact_tech_name" label="Technik – Name" :value="$gig->contact_tech_name" :max="100" />
<x-band-manager.field name="contact_tech_phone" label="Technik – Telefon" :value="$gig->contact_tech_phone" type="tel" :max="50" />

{!! $heading('info-circle', 'Weitere Infos') !!}
<x-band-manager.field name="guestlist" label="Gästeliste" :value="$gig->guestlist" placeholder="z.B. +2 pro Bandmitglied, bis 18 Uhr an …" />
<x-band-manager.field name="wifi" label="WLAN" :value="$gig->wifi" placeholder="Netz / Passwort" />
<x-band-manager.textarea name="backline_info" label="Backline / Technik" :value="$gig->backline_info"
                         placeholder="z.B. Drumkit wird gestellt (ohne Becken), eigene Amps mitbringen …" />
<x-band-manager.textarea name="merch_info" label="Merch" :value="$gig->merch_info"
                         placeholder="z.B. Stand im Foyer, 15 % Abgabe, eigene Person nötig …" />
<x-band-manager.textarea name="info_notes" label="Sonstiges (für die Band sichtbar)" :value="$gig->info_notes" />

@push('scripts')
    <script>
        // Buy-out-Betrag nur bei "Buy-out" bzw. "Catering + Buy-out" einblenden
        (() => {
            const sel = document.getElementById('dsCatering');
            const box = document.getElementById('dsBuyout');
            if (!sel || !box) return;
            sel.addEventListener('change', () => box.classList.toggle('d-none', !['buyout', 'both'].includes(sel.value)));
        })();
    </script>
@endpush
