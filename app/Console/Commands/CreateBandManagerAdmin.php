<?php

namespace App\Console\Commands;

use App\Models\BandManager\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;

#[Signature('band-manager:admin
    {email : E-Mail-Adresse für den Login unter /band-manager}
    {--name= : Anzeigename (Standard bei neuen Accounts: Administrator)}
    {--password= : Passwort direkt setzen statt interaktiv abfragen, z.B. auf Laravel Cloud}')]
#[Description('Legt einen Admin im Band-Manager an oder setzt dessen Passwort neu')]
class CreateBandManagerAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Ungültige E-Mail-Adresse.');

            return self::FAILURE;
        }

        $password = $this->option('password');

        if ($password !== null && mb_strlen($password) < 8) {
            $this->error('Das Passwort muss mindestens 8 Zeichen lang sein.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $this->option('name') ?? ($user->name ?: 'Administrator');
        $user->role = User::ROLE_ADMIN;
        $user->is_active = true;
        $user->password = $password ?? password(
            label: 'Passwort',
            required: true,
            validate: fn (string $value): ?string => mb_strlen($value) < 8 ? 'Mindestens 8 Zeichen.' : null,
        );
        $user->save();

        $this->info($user->wasRecentlyCreated
            ? "Admin '{$email}' angelegt."
            : "Admin '{$email}' aktualisiert.");

        return self::SUCCESS;
    }
}
