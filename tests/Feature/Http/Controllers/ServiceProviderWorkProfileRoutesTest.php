<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion der schreibenden Arbeitsprofil-Routen für Dienstleister (Rechte deckt die Rollen-Matrix ab).
 */
final class ServiceProviderWorkProfileRoutesTest extends FeatureTestCase
{
    private ServiceProvider $serviceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->serviceProvider = ServiceProvider::factory()->create();
    }

    #[Test]
    public function profile_image_is_stored_and_linked(): void
    {
        Storage::fake();

        $this->post(route('service_provider.change.profile-image', $this->serviceProvider), [
            'profileImage' => UploadedFile::fake()->image('avatar.png', 40, 40),
        ])->assertSuccessful();

        $profileImage = $this->serviceProvider->fresh()->getRawOriginal('profile_image');
        $this->assertNotNull($profileImage);
        $this->assertStringContainsString('profile-photos/', $profileImage);
        $this->assertCount(1, Storage::files('public/profile-photos'));
    }

    #[Test]
    public function profile_image_rejects_missing_and_non_image_files(): void
    {
        Storage::fake();

        $this->postJson(route('service_provider.change.profile-image', $this->serviceProvider), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('profileImage');

        $this->postJson(route('service_provider.change.profile-image', $this->serviceProvider), [
            'profileImage' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('profileImage');

        $this->assertNull($this->serviceProvider->fresh()->getRawOriginal('profile_image'));
    }

    #[Test]
    public function assign_craft_attaches_craft_once(): void
    {
        $craft = Craft::factory()->create();

        $this->patch(route('service_provider.assign.craft', $this->serviceProvider), ['craftId' => $craft->id])
            ->assertRedirect();
        // Doppelklick bzw. erneutes Zuweisen darf keine doppelte Pivot-Zeile erzeugen
        $this->patch(route('service_provider.assign.craft', $this->serviceProvider), ['craftId' => $craft->id])
            ->assertRedirect();

        $this->assertSame(1, $this->craftablesCount($craft));
    }

    #[Test]
    public function assign_craft_validates_craft_id_instead_of_failing_with_server_error(): void
    {
        $this->patchJson(route('service_provider.assign.craft', $this->serviceProvider), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('craftId');

        $this->patchJson(route('service_provider.assign.craft', $this->serviceProvider), ['craftId' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('craftId');

        $this->assertSame(0, $this->serviceProvider->assignedCrafts()->count());
    }

    #[Test]
    public function remove_craft_detaches_only_this_service_provider(): void
    {
        $craft = Craft::factory()->create();
        $otherServiceProvider = ServiceProvider::factory()->create();
        $this->serviceProvider->assignedCrafts()->attach($craft->id);
        $otherServiceProvider->assignedCrafts()->attach($craft->id);

        $this->delete(route('service_provider.remove.craft', [$this->serviceProvider, $craft]))->assertRedirect();

        $this->assertFalse($this->serviceProvider->assignedCrafts()->where('crafts.id', $craft->id)->exists());
        $this->assertTrue($otherServiceProvider->assignedCrafts()->where('crafts.id', $craft->id)->exists());
    }

    #[Test]
    public function remove_craft_that_is_not_assigned_is_a_noop(): void
    {
        $craft = Craft::factory()->create();

        $this->delete(route('service_provider.remove.craft', [$this->serviceProvider, $craft]))->assertRedirect();
        $this->delete(route('service_provider.remove.craft', [$this->serviceProvider, 999999]))->assertNotFound();
    }

    #[Test]
    public function craft_shift_qualification_toggles_per_craft(): void
    {
        $craftA = Craft::factory()->create();
        $craftB = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        $route = fn (Craft $craft) => route(
            'service_provider.update.craft-shift-qualification',
            [$this->serviceProvider, $craft, $qualification]
        );

        $this->patch($route($craftA))->assertRedirect();
        $this->patch($route($craftB))->assertRedirect();
        $this->assertSame(2, $this->qualifiablesCount($qualification));

        // Zweiter Klick auf Gewerk A entfernt nur dessen Zuordnung
        $this->patch($route($craftA))->assertRedirect();
        $remaining = $this->serviceProvider->shiftQualifications()->get();
        $this->assertCount(1, $remaining);
        $this->assertSame($craftB->id, (int) $remaining->first()->pivot->craft_id);
    }

    #[Test]
    public function craft_settings_store_boolean_flag(): void
    {
        $this->patch(route('service_provider.update.craftSettings', $this->serviceProvider), ['canBeAssignedToShifts' => true])
            ->assertRedirect();
        $this->assertTrue((bool) $this->serviceProvider->fresh()->can_work_shifts);

        $this->patch(route('service_provider.update.craftSettings', $this->serviceProvider), ['canBeAssignedToShifts' => false])
            ->assertRedirect();
        $this->assertFalse((bool) $this->serviceProvider->fresh()->can_work_shifts);
    }

    #[Test]
    public function work_profile_updates_name_and_description(): void
    {
        $this->patch(route('service_provider.update.workProfile', $this->serviceProvider), [
            'workName' => 'Licht',
            'workDescription' => 'Stellwerk und Verfolger',
        ])->assertRedirect();

        $fresh = $this->serviceProvider->fresh();
        $this->assertSame('Licht', $fresh->work_name);
        $this->assertSame('Stellwerk und Verfolger', $fresh->work_description);
    }

    #[Test]
    public function work_profile_rejects_overlong_values_instead_of_failing_with_server_error(): void
    {
        $this->patchJson(route('service_provider.update.workProfile', $this->serviceProvider), [
            'workName' => 'Licht',
            'workDescription' => str_repeat('x', 256),
        ])->assertStatus(422)->assertJsonValidationErrors('workDescription');

        $this->patchJson(route('service_provider.update.workProfile', $this->serviceProvider), [
            'workName' => ['kein', 'string'],
        ])->assertStatus(422)->assertJsonValidationErrors('workName');
    }

    #[Test]
    public function global_shift_qualification_toggles(): void
    {
        $qualification = GlobalQualification::factory()->create();
        $route = route('service_provider.update.shift-qualification', [$this->serviceProvider, $qualification]);

        $this->patch($route)->assertRedirect();
        $this->assertTrue($this->serviceProvider->globalQualifications()->whereKey($qualification->id)->exists());

        $this->patch($route)->assertRedirect();
        $this->assertFalse($this->serviceProvider->fresh()->globalQualifications()->whereKey($qualification->id)->exists());
    }

    private function craftablesCount(Craft $craft): int
    {
        return DB::table('craftables')
            ->where('craft_id', $craft->id)
            ->where('craftable_type', ServiceProvider::class)
            ->where('craftable_id', $this->serviceProvider->id)
            ->count();
    }

    private function qualifiablesCount(ShiftQualification $qualification): int
    {
        return DB::table('shift_qualifiables')
            ->where('shift_qualification_id', $qualification->id)
            ->where('qualifiable_type', $this->serviceProvider->getMorphClass())
            ->where('qualifiable_id', $this->serviceProvider->id)
            ->count();
    }
}
