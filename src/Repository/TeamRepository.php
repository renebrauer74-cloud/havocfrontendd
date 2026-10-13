<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class TeamRepository extends Repository
{
    protected const FIELDS = ['name', 'phone', 'email', 'hobbies', 'bio', 'image_url'];
}
