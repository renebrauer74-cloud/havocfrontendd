<?php
// Erzeugt einen Passwort-Hash für config/config.php:  php bin/hash-password.php "neues-passwort"
if ($argc < 2 || $argv[1] === '') {
    fwrite(STDERR, "Aufruf: php bin/hash-password.php \"passwort\"\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_DEFAULT), PHP_EOL;
