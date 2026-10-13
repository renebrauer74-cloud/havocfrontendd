<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class NewsRepository extends Repository
{
    protected const FIELDS = ['title', 'text', 'image_url'];

    /** Neueste zuerst. */
    public function latest(): array
    {
        return array_reverse($this->all());
    }
}
