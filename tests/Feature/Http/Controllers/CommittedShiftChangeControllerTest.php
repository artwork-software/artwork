<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CommittedShiftChangeControllerTest extends FeatureTestCase
{
    private function makeChange(array $attributes = []): CommittedShiftChange
    {
        return CommittedShiftChange::query()->forceCreate(array_merge([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'update',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ], $attributes));
    }

    #[Test]
    public function guest_cannot_acknowledge(): void
    {
        $change = CommittedShiftChange::query()->forceCreate([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'update',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ]);
        $this->post(route('committed-shift-changes.acknowledge', $change))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_acknowledge(): void
    {
        $admin = $this->actingAsAdmin();
        $change = CommittedShiftChange::query()->forceCreate([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'update',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ]);

        $response = $this->post(route('committed-shift-changes.acknowledge', $change));

        $response->assertRedirect();
        $this->assertNotNull($change->fresh()->acknowledged_at);
    }

    #[Test]
    public function guest_cannot_acknowledge_all(): void
    {
        $this->post(route('committed-shift-changes.acknowledge-all'), ['craft_id' => 1])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function acknowledge_all_requires_craft(): void
    {
        $this->actingAsAdmin();

        $this->post(route('committed-shift-changes.acknowledge-all'))
            ->assertSessionHasErrors('craft_id');
    }

    #[Test]
    public function acknowledge_all_only_affects_open_changes_of_given_craft(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();

        $open = $this->makeChange(['craft_id' => $craft->id]);
        $alreadyAcked = $this->makeChange([
            'craft_id' => $craft->id,
            'acknowledged_at' => now()->subDay(),
            'acknowledged_by_user_id' => null,
        ]);
        $otherCraftOpen = $this->makeChange(['craft_id' => $otherCraft->id]);

        $this->post(route('committed-shift-changes.acknowledge-all'), [
            'craft_id' => $craft->id,
        ])->assertRedirect();

        $this->assertNotNull($open->fresh()->acknowledged_at);
        $this->assertSame($admin->id, $open->fresh()->acknowledged_by_user_id);
        // Bereits genehmigte Einträge behalten Zeitpunkt/Genehmiger.
        $this->assertNull($alreadyAcked->fresh()->acknowledged_by_user_id);
        // Andere Gewerke bleiben unangetastet.
        $this->assertNull($otherCraftOpen->fresh()->acknowledged_at);
    }

    #[Test]
    public function acknowledge_all_respects_worker_type_filter(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $internalUser = User::factory()->create(['is_freelancer' => false]);
        $flaggedUser = User::factory()->create(['is_freelancer' => true]);
        $freelancer = Freelancer::factory()->create();

        $internalChange = $this->makeChange([
            'craft_id' => $craft->id,
            'affected_user_type' => User::class,
            'affected_user_id' => $internalUser->id,
        ]);
        $flaggedUserChange = $this->makeChange([
            'craft_id' => $craft->id,
            'affected_user_type' => User::class,
            'affected_user_id' => $flaggedUser->id,
        ]);
        $freelancerChange = $this->makeChange([
            'craft_id' => $craft->id,
            'affected_user_type' => Freelancer::class,
            'affected_user_id' => $freelancer->id,
        ]);
        // Reine Schicht-Änderung ohne betroffene Person: zählt zu beiden Ansichten.
        $neutralChange = $this->makeChange(['craft_id' => $craft->id]);

        $this->post(route('committed-shift-changes.acknowledge-all'), [
            'craft_id' => $craft->id,
            'worker_type' => 'external',
        ])->assertRedirect();

        $this->assertNull($internalChange->fresh()->acknowledged_at);
        $this->assertNotNull($flaggedUserChange->fresh()->acknowledged_at);
        $this->assertNotNull($freelancerChange->fresh()->acknowledged_at);
        $this->assertNotNull($neutralChange->fresh()->acknowledged_at);

        $this->post(route('committed-shift-changes.acknowledge-all'), [
            'craft_id' => $craft->id,
            'worker_type' => 'internal',
        ])->assertRedirect();

        $this->assertNotNull($internalChange->fresh()->acknowledged_at);
    }
}
