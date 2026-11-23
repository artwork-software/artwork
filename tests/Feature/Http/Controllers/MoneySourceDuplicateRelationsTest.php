<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Duplizieren übernimmt Personen samt Rechten und die Kategorien der Quelle (Wunsch 16.11.2026). */
final class MoneySourceDuplicateRelationsTest extends FeatureTestCase
{
    #[Test]
    public function duplicate_copies_people_with_rights_and_categories(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create(['source_name' => 'Duplikat-Quelle']);
        $competent = User::factory()->create();
        $writer = User::factory()->create();
        $source->users()->attach($competent->id, ['competent' => true, 'write_access' => false]);
        $source->users()->attach($writer->id, ['competent' => false, 'write_access' => true]);
        $category = MoneySourceCategory::query()->create(['name' => 'Kulturförderung']);
        $source->categories()->attach($category->id);

        $this->post(route('money_sources.duplicate', $source))->assertRedirect(route('money_sources.index'));

        $copy = MoneySource::query()->whereKeyNot($source->id)->where('source_name', 'Duplikat-Quelle')->sole();
        $rights = DB::table('money_source_users')->where('money_source_id', $copy->id)
            ->get(['user_id', 'competent', 'write_access'])->keyBy('user_id');
        self::assertEqualsCanonicalizing([$competent->id, $writer->id], $rights->keys()->all());
        self::assertSame([1, 0], [(int) $rights[$competent->id]->competent, (int) $rights[$competent->id]->write_access]);
        self::assertSame([0, 1], [(int) $rights[$writer->id]->competent, (int) $rights[$writer->id]->write_access]);
        self::assertSame([$category->id], $copy->categories()->pluck('money_source_categories.id')->all());

        // Quelle bleibt unverändert
        self::assertSame(2, $source->users()->count());
        self::assertSame(1, $source->categories()->count());
    }
}
