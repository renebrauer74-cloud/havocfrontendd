@props(['person'])
{{-- Initialen einer Person, wenn kein Foto hinterlegt ist --}}
<span class="tm-initials">{{ mb_strtoupper(mb_substr((string) $person->first_name, 0, 1).mb_substr((string) $person->last_name, 0, 1)) }}</span>
