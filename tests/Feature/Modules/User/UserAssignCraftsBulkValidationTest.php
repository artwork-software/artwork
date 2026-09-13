<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Härtung: Gewerks-Sammelzuweisung an Nutzer*innen validiert die IDs (array, exists, Deckel).
 */
final class UserAssignCraftsBulkValidationTest extends FeatureTestCase
{
    #[Test]
    public function invalid_craft_ids_are_rejected(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson(route('user.assign.crafts.bulk', $user), ['craftIds' => 'nope'])->assertStatus(422);
        $this->patchJson(route('user.assign.crafts.bulk', $user), ['craftIds' => [999999]])->assertStatus(422);
    }

    #[Test]
    public function valid_craft_ids_are_assigned(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $craft = Craft::factory()->create();

        $this->patch(route('user.assign.crafts.bulk', $user), ['craftIds' => [$craft->id]])->assertRedirect();

        $this->assertTrue($user->fresh()->assignedCrafts()->where('craft_id', $craft->id)->exists());
    }
}
