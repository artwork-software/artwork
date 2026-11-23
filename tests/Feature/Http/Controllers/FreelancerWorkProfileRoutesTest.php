<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion der schreibenden Arbeitsprofil-Routen für Freelancer (Rechte deckt die Rollen-Matrix ab).
 */
final class FreelancerWorkProfileRoutesTest extends FeatureTestCase
{
    private Freelancer $freelancer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->freelancer = Freelancer::factory()->create();
    }

    #[Test]
    public function profile_image_is_stored_and_linked(): void
    {
        Storage::fake();

        $this->post(route('freelancer.change.profile-image', $this->freelancer), [
            'profileImage' => UploadedFile::fake()->image('avatar.png', 40, 40),
        ])->assertSuccessful();

        $profileImage = $this->freelancer->fresh()->getRawOriginal('profile_image');
        $this->assertNotNull($profileImage);
        $this->assertStringContainsString('profile-photos/', $profileImage);
        $this->assertCount(1, Storage::files('public/profile-photos'));
    }

    #[Test]
    public function profile_image_rejects_missing_and_non_image_files(): void
    {
        Storage::fake();

        $this->postJson(route('freelancer.change.profile-image', $this->freelancer), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('profileImage');

        $this->postJson(route('freelancer.change.profile-image', $this->freelancer), [
            'profileImage' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('profileImage');

        $this->assertNull($this->freelancer->fresh()->getRawOriginal('profile_image'));
    }

    #[Test]
    public function assign_craft_attaches_craft_once(): void
    {
        $craft = Craft::factory()->create();

        $this->patch(route('freelancer.assign.craft', $this->freelancer), ['craftId' => $craft->id])
            ->assertRedirect();
        // Doppelklick bzw. erneutes Zuweisen darf keine doppelte Pivot-Zeile erzeugen
        $this->patch(route('freelancer.assign.craft', $this->freelancer), ['craftId' => $craft->id])
            ->assertRedirect();

        $this->assertSame(1, $this->craftablesCount($craft));
    }

    #[Test]
    public function assign_craft_validates_craft_id_instead_of_failing_with_server_error(): void
    {
        $this->patchJson(route('freelancer.assign.craft', $this->freelancer), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('craftId');

        $this->patchJson(route('freelancer.assign.craft', $this->freelancer), ['craftId' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('craftId');

        $this->assertSame(0, $this->freelancer->assignedCrafts()->count());
    }

    #[Test]
    public function remove_craft_detaches_only_this_freelancer(): void
    {
        $craft = Craft::factory()->create();
        $otherFreelancer = Freelancer::factory()->create();
        $this->freelancer->assignedCrafts()->attach($craft->id);
        $otherFreelancer->assignedCrafts()->attach($craft->id);

        $this->delete(route('freelancer.remove.craft', [$this->freelancer, $craft]))->assertRedirect();

        $this->assertFalse($this->freelancer->assignedCrafts()->where('crafts.id', $craft->id)->exists());
        $this->assertTrue($otherFreelancer->assignedCrafts()->where('crafts.id', $craft->id)->exists());
    }

    #[Test]
    public function remove_craft_that_is_not_assigned_is_a_noop(): void
    {
        $craft = Craft::factory()->create();

        $this->delete(route('freelancer.remove.craft', [$this->freelancer, $craft]))->assertRedirect();
        $this->delete(route('freelancer.remove.craft', [$this->freelancer, 999999]))->assertNotFound();
    }

    #[Test]
    public function craft_shift_qualification_toggles_per_craft(): void
    {
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $route = fn (Craft $craft) => route(
            'freelancer.update.craft-shift-qualification',
            [$this->freelancer, $craft, $qualification]
        );

        $this->patch($route($craftA))->assertRedirect();
        $this->patch($route($craftB))->assertRedirect();
        $this->assertSame(2, $this->qualifiablesCount($qualification));

        // Zweiter Klick auf Gewerk A entfernt nur dessen Zuordnung
        $this->patch($route($craftA))->assertRedirect();
        $remaining = $this->freelancer->shiftQualifications()->get();
        $this->assertCount(1, $remaining);
        $this->assertSame($craftB->id, (int) $remaining->first()->pivot->craft_id);
    }

    #[Test]
    public function craft_settings_store_boolean_flag(): void
    {
        $this->patch(route('freelancer.update.craftSettings', $this->freelancer), ['canBeAssignedToShifts' => true])
            ->assertRedirect();
        $this->assertTrue((bool) $this->freelancer->fresh()->can_work_shifts);

        $this->patch(route('freelancer.update.craftSettings', $this->freelancer), ['canBeAssignedToShifts' => false])
            ->assertRedirect();
        $this->assertFalse((bool) $this->freelancer->fresh()->can_work_shifts);
    }

    #[Test]
    public function work_profile_updates_name_and_description(): void
    {
        $this->patch(route('freelancer.update.workProfile', $this->freelancer), [
            'workName' => 'Licht',
            'workDescription' => 'Stellwerk und Verfolger',
        ])->assertRedirect();

        $fresh = $this->freelancer->fresh();
        $this->assertSame('Licht', $fresh->work_name);
        $this->assertSame('Stellwerk und Verfolger', $fresh->work_description);
    }

    /**
     * Die Externenverwaltung pflegt das Arbeitsprofil von Freelancer:innen auch ohne Personalverwaltung
     * (wie bei Dienstleistern); ohne eines der beiden Rechte bleibt es verboten.
     */
    #[Test]
    public function work_profile_can_be_saved_with_the_external_workers_permission_alone(): void
    {
        $this->actingAsUserWith([PermissionEnum::EXTERNAL_MANAGER->value]);

        $this->patch(route('freelancer.update.workProfile', $this->freelancer), [
            'workName' => 'Ton',
            'workDescription' => 'FOH',
        ])->assertRedirect();
        $this->assertSame('Ton', $this->freelancer->fresh()->work_name);

        $this->actingAsUserWith([]);
        $this->patch(route('freelancer.update.workProfile', $this->freelancer), ['workName' => 'Nein'])
            ->assertForbidden();
    }

    #[Test]
    public function work_profile_rejects_overlong_values_instead_of_failing_with_server_error(): void
    {
        $this->patchJson(route('freelancer.update.workProfile', $this->freelancer), [
            'workName' => 'Licht',
            'workDescription' => str_repeat('x', 256),
        ])->assertStatus(422)->assertJsonValidationErrors('workDescription');

        $this->patchJson(route('freelancer.update.workProfile', $this->freelancer), [
            'workName' => ['kein', 'string'],
        ])->assertStatus(422)->assertJsonValidationErrors('workName');
    }

    #[Test]
    public function global_shift_qualification_toggles(): void
    {
        $qualification = GlobalQualification::factory()->create();
        $route = route('freelancer.update.shift-qualification', [$this->freelancer, $qualification]);

        $this->patch($route)->assertRedirect();
        $this->assertTrue($this->freelancer->globalQualifications()->whereKey($qualification->id)->exists());

        $this->patch($route)->assertRedirect();
        $this->assertFalse($this->freelancer->fresh()->globalQualifications()->whereKey($qualification->id)->exists());
    }

    private function craftablesCount(Craft $craft): int
    {
        return DB::table('craftables')
            ->where('craft_id', $craft->id)
            ->where('craftable_type', Freelancer::class)
            ->where('craftable_id', $this->freelancer->id)
            ->count();
    }

    private function qualifiablesCount(ShiftQualification $qualification): int
    {
        return DB::table('shift_qualifiables')
            ->where('shift_qualification_id', $qualification->id)
            ->where('qualifiable_type', $this->freelancer->getMorphClass())
            ->where('qualifiable_id', $this->freelancer->id)
            ->count();
    }
}
