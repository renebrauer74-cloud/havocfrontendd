<?php

use App\Models\Artist;
use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an admin user with a prompted password', function () {
    $this->artisan('havoc:admin', ['username' => 'rene'])
        ->expectsQuestion('Passwort', 'ein-sicheres-passwort')
        ->expectsOutput("Admin 'rene' angelegt.")
        ->assertSuccessful();

    expect(Hash::check('ein-sicheres-passwort', User::sole()->password))->toBeTrue();
});

it('takes over an existing bcrypt hash from the old config', function () {
    $hash = Hash::make('altes-passwort');

    $this->artisan('havoc:admin', ['username' => 'admin', '--password-hash' => $hash])->assertSuccessful();

    expect(Hash::check('altes-passwort', User::sole()->password))->toBeTrue();
});

it('refuses hashes that are not bcrypt', function () {
    $this->artisan('havoc:admin', ['username' => 'admin', '--password-hash' => 'klartext'])->assertFailed();

    expect(User::count())->toBe(0);
});

it('imports the json data of the old version and keeps the ids', function () {
    $directory = sys_get_temp_dir().'/havoc-import-'.uniqid();
    mkdir($directory);
    file_put_contents($directory.'/artists.json', json_encode(['next_id' => 6, 'rows' => [
        ['id' => 5, 'name' => 'Alt-Band', 'genre' => '', 'image_url' => 'uploads/artists/a.jpg', 'created_at' => '2026-10-13T22:49:52+02:00', 'unbekannt' => 'x'],
    ]]));
    file_put_contents($directory.'/gallery.json', json_encode(['next_id' => 2, 'rows' => [
        ['id' => 1, 'artist_id' => 5, 'image_url' => 'uploads/gallery/b.jpg', 'created_at' => '2026-10-13T22:50:11+02:00'],
    ]]));

    $this->artisan('havoc:import-json', ['path' => $directory])->assertSuccessful();

    $artist = Artist::sole();
    expect($artist->id)->toBe(5)
        ->and($artist->created_at->format('Y-m-d H:i'))->toBe('2026-10-13 22:49')
        ->and(GalleryImage::sole()->artist->is($artist))->toBeTrue();

    array_map(unlink(...), glob($directory.'/*.json'));
    rmdir($directory);
});

it('refuses to import into tables that already contain data', function () {
    Artist::factory()->create();

    $this->artisan('havoc:import-json', ['path' => sys_get_temp_dir()])->assertFailed();
});
