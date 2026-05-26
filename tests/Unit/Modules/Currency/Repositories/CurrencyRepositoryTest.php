<?php

namespace Tests\Unit\Modules\Currency\Repositories;

use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Currency\Repositories\CurrencyRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CurrencyRepositoryTest extends TestCase
{
    private CurrencyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CurrencyRepository::class);
    }

    #[Test]
    public function get_all_returns_currencies(): void
    {
        Currency::factory()->count(5)->create();

        $result = $this->repository->getAll();

        $this->assertCount(5, $result);
    }
}
