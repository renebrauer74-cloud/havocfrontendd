<?php

use App\Services\BandManager\Markdown;

it('converts the supported formatting to safe html', function (string $text, string $expected) {
    expect((new Markdown)->toHtml($text))->toBe($expected);
})->with([
    'bold' => ['**laut**', '<strong>laut</strong>'],
    'italic' => ['*leise*', '<em>leise</em>'],
    'underline' => ['__wichtig__', '<u>wichtig</u>'],
    'strike' => ['~~alt~~', '<s>alt</s>'],
    'escaped marker' => ['\*kein\* Stern', '*kein* Stern'],
    'html is escaped' => ['<b>**x**</b>', '&lt;b&gt;<strong>x</strong>&lt;/b&gt;'],
    'line breaks' => ["a\nb", "a<br />\nb"],
    'markers around spaces stay' => ['* nicht *', '* nicht *'],
]);

it('strips the formatting for plain text', function () {
    expect((new Markdown)->plain('**fett** und *kursiv*'))->toBe('fett und kursiv');
});

it('renders a legend with keyboard shortcuts', function () {
    expect((new Markdown)->legend())
        ->toContain('data-md-marker="**"')
        ->toContain('data-md-key="b"');
});
