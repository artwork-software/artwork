<?php

namespace Tests\Feature\Modules\Craft;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * currentUserCrafts (Schicht-Dialog): welche Gewerke die Person zum Anlegen von Schichten sieht —
 * mit Funktionen, aber ohne die Planer:innen-Liste (volle User-Modelle, vom Dialog nie gelesen).
 */
final class CraftsForShiftCreationTest extends FeatureTestCase
{
    #[Test]
    public function admin_gets_every_craft_with_qualifications_but_without_planers(): void
    {
        $admin = $this->adminUser();
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $craft->qualifications()->attach(ShiftQualification::factory()->create()->id);
        $craft->craftShiftPlaner()->attach(User::factory()->create()->id);

        $serialized = collect(app(CraftService::class)->getCraftsForShiftCreation($admin)->toArray())
            ->firstWhere('id', $craft->id);

        $this->assertNotNull($serialized);
        $this->assertCount(1, $serialized['qualifications']);
        $this->assertArrayNotHasKey('craft_shift_planer', $serialized);
    }

    #[Test]
    public function other_users_get_crafts_assignable_by_all_and_their_own(): void
    {
        $user = User::factory()->create();
        $open = Craft::factory()->create(['assignable_by_all' => true]);
        $own = Craft::factory()->create(['assignable_by_all' => false]);
        $own->craftShiftPlaner()->attach($user->id);
        $foreign = Craft::factory()->create(['assignable_by_all' => false]);

        $crafts = app(CraftService::class)->getCraftsForShiftCreation($user);
        $ids = $crafts->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertSame(count($ids), count(array_unique($ids)));
        foreach ($crafts->toArray() as $serialized) {
            $this->assertArrayHasKey('qualifications', $serialized);
            $this->assertArrayNotHasKey('craft_shift_planer', $serialized);
        }
    }
}
