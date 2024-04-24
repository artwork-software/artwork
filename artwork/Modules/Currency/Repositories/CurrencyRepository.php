<?php

namespace Artwork\Modules\Currency\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

readonly class CurrencyRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return Currency::all();
    }
}
