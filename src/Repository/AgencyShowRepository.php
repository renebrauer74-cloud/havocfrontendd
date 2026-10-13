<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class AgencyShowRepository extends Repository
{
    protected const FIELDS = ['title', 'date', 'city', 'venue', 'ticket_url', 'info_text', 'image_url'];

    public function all(): array
    {
        return sort_by_show_date(parent::all());
    }
}
