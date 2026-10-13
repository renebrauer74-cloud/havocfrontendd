<?php
declare(strict_types=1);

namespace Havoc\Repository;

/** Anfragen aus dem Kontaktformular. */
final class MessageRepository extends Repository
{
    protected const FIELDS = ['name', 'email', 'message'];

    public function latest(): array
    {
        return array_reverse($this->all());
    }
}
