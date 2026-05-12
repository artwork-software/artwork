<?php

namespace Tests\Unit\Modules\Currency\Models;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Currency\Models\Currency;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CurrencyTest extends TestCase
{
    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $currency = Currency::factory()->create();

        $currency->delete();

        $this->assertSoftDeleted($currency);
        $this->assertNotNull($currency->fresh()?->deleted_at);
    }

    #[Test]
    public function it_belongs_to_many_contracts(): void
    {
        $currency = Currency::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $currency->contracts()
        );
        $this->assertSame(Contract::class, get_class($currency->contracts()->getRelated()));
    }

    #[Test]
    public function prunable_only_includes_currencies_deleted_more_than_a_month_ago(): void
    {
        $oldDeleted = Currency::factory()->create();
        $oldDeleted->delete();
        $oldDeleted->forceFill(['deleted_at' => Carbon::now()->subMonths(2)])->save();

        $recentlyDeleted = Currency::factory()->create();
        $recentlyDeleted->delete();

        Currency::factory()->create();

        $prunable = Currency::factory()->newModel()->prunable()->get();

        $this->assertTrue($prunable->contains('id', $oldDeleted->id));
        $this->assertFalse($prunable->contains('id', $recentlyDeleted->id));
    }
}
