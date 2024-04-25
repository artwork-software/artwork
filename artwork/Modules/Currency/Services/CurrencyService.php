<?php

namespace Artwork\Modules\Currency\Services;

use Artwork\Modules\Currency\Repositories\CurrencyRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CurrencyService
{
    public function __construct(private CurrencyRepository $currencyRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->currencyRepository->getAll();
    }
}
