<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;

#[Signature('havoc:admin
    {username : Login-Name für das Backend}
    {--password-hash= : Vorhandenen bcrypt-Hash übernehmen, z.B. password_hash aus der alten config/config.php}')]
#[Description('Legt einen Admin-Zugang an oder setzt dessen Passwort neu')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));
        $hash = $this->option('password-hash');

        if ($hash !== null && ! str_starts_with($hash, '$2y$')) {
            $this->error('Der Hash muss ein bcrypt-Hash sein ($2y$...).');

            return self::FAILURE;
        }

        $password = $hash ?? Hash::make(password(
            label: 'Passwort',
            required: true,
            validate: fn (string $value): ?string => mb_strlen($value) < 10 ? 'Mindestens 10 Zeichen.' : null,
        ));

        $user = User::query()->firstOrNew(['username' => $username]);
        $user->password = $password;
        $user->save();

        $this->info($user->wasRecentlyCreated
            ? "Admin '{$username}' angelegt."
            : "Passwort für '{$username}' aktualisiert.");

        return self::SUCCESS;
    }
}
