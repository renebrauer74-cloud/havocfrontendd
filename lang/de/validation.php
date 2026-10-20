<?php

/*
|--------------------------------------------------------------------------
| Validierungsmeldungen (Deutsch)
|--------------------------------------------------------------------------
|
| Nur die Regeln, die diese Anwendung verwendet. Alles andere fällt auf die
| englischen Standardtexte des Frameworks zurück (APP_FALLBACK_LOCALE).
|
*/

return [

    'confirmed' => 'Die Wiederholung von :attribute stimmt nicht überein.',
    'current_password' => 'Das Passwort ist nicht korrekt.',
    'email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
    'exists' => 'Bitte eine gültige Auswahl für :attribute treffen.',
    'image' => ':attribute muss ein Bild sein.',
    'integer' => ':attribute muss eine Zahl sein.',
    'max' => [
        'array' => ':attribute darf höchstens :max Einträge haben.',
        'file' => ':attribute ist zu groß (höchstens :max KB).',
        'numeric' => ':attribute darf höchstens :max sein.',
        'string' => ':attribute darf höchstens :max Zeichen lang sein.',
    ],
    'mimes' => 'Bitte nur Bilder hochladen (JPG, PNG, GIF, WebP).',
    'min' => [
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
    ],
    'required' => 'Bitte :attribute ausfüllen.',
    'string' => ':attribute muss ein Text sein.',
    'unique' => ':attribute ist bereits vergeben.',
    'uploaded' => ':attribute konnte nicht hochgeladen werden – vermutlich ist die Datei zu groß.',

    'attributes' => [
        'artist_id' => 'Band',
        'bio' => 'Biografie (DE)',
        'bio_en' => 'Biography (EN)',
        'city' => 'Stadt',
        'date' => 'Datum',
        'email' => 'E-Mail-Adresse',
        'genre' => 'Genre',
        'hobbies' => 'Hobbys',
        'image' => 'Das Bild',
        'info_text' => 'Infotext',
        'link' => 'Ticket-Link',
        'message' => 'Nachricht',
        'name' => 'Name',
        'password' => 'Passwort',
        'phone' => 'Telefonnummer',
        'text' => 'Inhalt',
        'ticket_url' => 'Ticket-Link',
        'title' => 'Titel',
        'username' => 'Username',
        'venue' => 'Location',
    ],

];
