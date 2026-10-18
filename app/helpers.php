<?php

use App\Services\BandManager\Markdown;

/**
 * View-Helfer für externe Links aus dem Admin.
 */
if (! function_exists('safe_link')) {
    /**
     * Externe Links aus dem Admin absichern: nur http(s) und mailto, sonst '#'.
     */
    function safe_link(?string $link): string
    {
        $link = trim((string) $link);

        if ($link === '') {
            return '#';
        }

        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) {
            return preg_match('#^(https?://|mailto:)#i', $link) ? $link : '#';
        }

        return 'https://'.ltrim($link, '/');
    }
}

if (! function_exists('placeholder_image')) {
    /**
     * Platzhalterbild als Data-URI, wenn kein Bild hinterlegt ist.
     */
    function placeholder_image(int $width, int $height, string $label, int $fontSize = 16): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">'
            .'<rect width="%1$d" height="%2$d" fill="#000"/>'
            .'<text x="50%%" y="50%%" font-family="Arial" font-size="%3$d" font-weight="900" fill="#fff" '
            .'text-anchor="middle" dominant-baseline="middle">%4$s</text></svg>',
            $width,
            $height,
            $fontSize,
            htmlspecialchars($label, ENT_XML1)
        );

        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($svg);
    }
}

/**
 * Band-Manager: Freitexte mit Mini-Markdown (**fett**, *kursiv* …), siehe App\Services\BandManager\Markdown.
 */
if (! function_exists('md')) {
    /**
     * Freitext als sicheres HTML – Ausgabe in Blade mit {!! md($text) !!}.
     */
    function md(?string $text): string
    {
        return app(Markdown::class)->toHtml($text);
    }
}

if (! function_exists('md_plain')) {
    /**
     * Freitext ohne Formatierungszeichen (Vorschauen, Tooltips) – Ausgabe weiterhin mit {{ }}.
     */
    function md_plain(?string $text): string
    {
        return app(Markdown::class)->plain($text);
    }
}

if (! function_exists('md_legend')) {
    /**
     * Legende mit Formatier-Buttons für Textfelder mit data-md.
     */
    function md_legend(): string
    {
        return app(Markdown::class)->legend();
    }
}
