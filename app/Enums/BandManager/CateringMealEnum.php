<?php

namespace App\Enums\BandManager;

/**
 * Mahlzeiten im Catering Rider – in der Reihenfolge eines Show-Tages.
 */
enum CateringMealEnum: string
{
    case Breakfast = 'breakfast';
    case Arrival = 'arrival';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Aftershow = 'aftershow';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Frühstück',
            self::Arrival => 'Snacks bei Ankunft',
            self::Lunch => 'Mittagessen',
            self::Dinner => 'Warmes Abendessen',
            self::Aftershow => 'Aftershow / Nachtsnack',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Breakfast => 'cup-hot',
            self::Arrival => 'basket',
            self::Lunch => 'egg-fried',
            self::Dinner => 'fire',
            self::Aftershow => 'moon-stars',
        };
    }

    /**
     * Vorschlag für den Zeitpunkt (Platzhalter im Formular).
     */
    public function timeHint(): string
    {
        return match ($this) {
            self::Breakfast => 'z. B. bis 10:00 Uhr im Hotel',
            self::Arrival => 'z. B. ab Get-in bereitstehend',
            self::Lunch => 'z. B. 13:00 Uhr',
            self::Dinner => 'z. B. spätestens 2 Std. vor Showbeginn',
            self::Aftershow => 'z. B. nach der Show in der Garderobe',
        };
    }

    /**
     * Vorschlag für die Wünsche (Platzhalter im Formular).
     */
    public function wishesHint(): string
    {
        return match ($this) {
            self::Breakfast => 'z. B. Kaffee, Brötchen, Aufschnitt & Käse, vegane Aufstriche, Müsli, Obst, Eier',
            self::Arrival => 'z. B. frisches Obst, Gemüsesticks mit Dip, Nüsse, Brot, Schokolade, Chips',
            self::Lunch => 'z. B. leichtes warmes Gericht oder belegte Brötchen, Salat',
            self::Dinner => 'z. B. ausgewogenes warmes Essen mit Fleisch- und veganer Option, Salat, Dessert – bitte keine Pizza',
            self::Aftershow => 'z. B. belegte Brötchen oder Wraps für die Fahrt, Obst',
        };
    }
}
