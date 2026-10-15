<?php

namespace App\Services\BandManager;

/**
 * Mini-Markdown für Freitexte (Biografie, Steckbrief, Nachrichten, Day Sheet …)
 *
 *   **fett**   *kursiv*   __unterstrichen__   ~~durchgestrichen~~
 *   \*  → Sternchen ohne Formatierung
 *
 * Neue Formatierung? → eine Zeile in RULES ergänzen.
 * Legende, Buttons, Tastenkürzel, Webansicht und PDF passen sich automatisch an.
 *
 * Sicherheit: Der Text wird IMMER zuerst komplett escaped – es entstehen nur die hier erlaubten Tags.
 */
class Markdown
{
    /**
     * marker = Zeichen um den Text (keine < > & " ' verwenden)
     * tag    = HTML-Tag (nur Buchstaben/Zahlen)
     * label  = Text in der Legende
     * key    = Tastenkürzel Strg/⌘ + key (optional)
     * Reihenfolge = Reihenfolge in der Legende
     *
     * @var array<string, array{marker: string, tag: string, label: string, key?: string}>
     */
    private const RULES = [
        'bold' => ['marker' => '**', 'tag' => 'strong', 'label' => 'fett', 'key' => 'b'],
        'italic' => ['marker' => '*', 'tag' => 'em', 'label' => 'kursiv', 'key' => 'i'],
        'underline' => ['marker' => '__', 'tag' => 'u', 'label' => 'unterstrichen', 'key' => 'u'],
        'strike' => ['marker' => '~~', 'tag' => 's', 'label' => 'durchgestrichen'],
    ];

    /**
     * Text → sicheres HTML (mit Zeilenumbrüchen).
     */
    public function toHtml(?string $text, bool $breaks = true): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        [$html, $restore] = $this->protect(e($text));

        foreach ($this->ordered() as $rule) {
            $html = preg_replace($this->pattern($rule['marker']), "<{$rule['tag']}>$1</{$rule['tag']}>", $html) ?? $html;
        }

        $html = $restore($html);

        return $breaks ? nl2br($html) : $html;
    }

    /**
     * Text ohne Formatierungszeichen (Vorschauen, Tooltips) – Ausgabe weiterhin escapen!
     */
    public function plain(?string $text): string
    {
        [$plain, $restore] = $this->protect((string) $text);

        foreach ($this->ordered() as $rule) {
            $plain = preg_replace($this->pattern($rule['marker']), '$1', $plain) ?? $plain;
        }

        return $restore($plain);
    }

    /**
     * Legende (Chips) – wird per markdown.js unter jedes <textarea data-md> gesetzt.
     */
    public function legend(): string
    {
        $chips = '';

        foreach (self::RULES as $rule) {
            $marker = e($rule['marker']);
            $key = $rule['key'] ?? '';

            $chips .= sprintf(
                '<button type="button" class="hv-md-chip" data-md-marker="%s"%s title="%s">'
                .'<span class="hv-md-syntax">%s</span><%s>%s</%s><span class="hv-md-syntax">%s</span></button>',
                $marker,
                $key !== '' ? ' data-md-key="'.e($key).'"' : '',
                e($rule['marker'].'Text'.$rule['marker'].' → '.$rule['label'].($key !== '' ? ' · Strg/⌘+'.strtoupper($key) : '')),
                $marker, $rule['tag'], e($rule['label']), $rule['tag'], $marker
            );
        }

        return '<div class="hv-md-legend"><span class="hv-md-label"><i class="bi bi-type"></i> Formatieren:</span>'.$chips.'</div>';
    }

    /**
     * Längere Zeichen zuerst (** vor *), damit sich Regeln nicht in die Quere kommen.
     *
     * @return list<array{marker: string, tag: string, label: string, key?: string}>
     */
    private function ordered(): array
    {
        $rules = array_values(self::RULES);
        usort($rules, fn (array $a, array $b): int => strlen($b['marker']) <=> strlen($a['marker']));

        return $rules;
    }

    /**
     * **Text** – nicht mit Leerzeichen beginnen/enden, nur innerhalb einer Zeile.
     */
    private function pattern(string $marker): string
    {
        $quoted = preg_quote($marker, '/');
        $first = preg_quote($marker[0], '/');

        return '/(?<!'.$first.')'.$quoted.'(?=\S)([^\n]+?)(?<=\S)'.$quoted.'(?!'.$first.')/u';
    }

    /**
     * \* \_ \~ \\ → Zeichen bleibt unformatiert. Liefert den geschützten Text und eine Funktion zum Zurücksetzen.
     *
     * @return array{0: string, 1: \Closure(string): string}
     */
    private function protect(string $text): array
    {
        $text = str_replace(["\u{E000}", "\u{E001}"], '', $text);
        $chars = array_unique(array_map(fn (array $rule): string => $rule['marker'][0], self::RULES));
        $class = preg_quote(implode('', $chars).'\\', '/');

        $text = preg_replace_callback('/\\\\(['.$class.'])/u', fn (array $m): string => "\u{E000}".ord($m[1])."\u{E001}", $text) ?? $text;

        $restore = fn (string $html): string => preg_replace_callback('/\x{E000}(\d+)\x{E001}/u', fn (array $m): string => chr((int) $m[1]), $html) ?? $html;

        return [$text, $restore];
    }
}
