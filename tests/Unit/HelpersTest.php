<?php

use App\Models\Collections\ShowDateCollection;

it('secures external links', function (string $input, string $expected) {
    expect(safe_link($input))->toBe($expected);
})->with([
    'empty' => ['', '#'],
    'https' => ['https://example.com', 'https://example.com'],
    'mailto' => ['mailto:info@havoc-agency.com', 'mailto:info@havoc-agency.com'],
    'without scheme' => ['open.spotify.com/x', 'https://open.spotify.com/x'],
    'javascript' => ['javascript:alert(1)', '#'],
    'data uri' => ['data:text/html,boom', '#'],
]);

it('parses the supported free-text date formats', function (string $date, ?string $expected) {
    $timestamp = ShowDateCollection::parseDate($date);

    expect($timestamp === null ? null : date('Y-m-d', $timestamp))->toBe($expected);
})->with([
    ['24.05.2026', '2026-05-24'],
    ['4.5.2026', '2026-05-04'],
    ['24.05.26', '2026-05-24'],
    ['2026-05-24', '2026-05-24'],
    ['12.-14. JUNI 2026', null],
    ['', null],
]);

it('builds an svg placeholder image', function () {
    expect(placeholder_image(100, 100, 'HAVOC & CO'))
        ->toStartWith('data:image/svg+xml;charset=utf-8,')
        ->toContain(rawurlencode('HAVOC &amp; CO'));
});
