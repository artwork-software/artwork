<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class VacationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $user = User::factory()->create();
        $this->post(route('user.vacation.add', $user), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $vacation = Vacation::factory()->create();
        $this->delete(route('delete.vacation', $vacation))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_vacation(): void
    {
        $this->actingAsAdmin();
        $vacation = Vacation::factory()->create();

        $response = $this->delete(route('delete.vacation', $vacation));

        $response->assertRedirect();
    }

    /**
     * Verfügbarkeitsstatus (Arbeitsfrei/Frei/…) ist Planungssache: ohne Planungs- bzw.
     * Verfügbarkeits-Permission darf niemand ihn setzen — auch nicht für sich selbst.
     */
    #[Test]
    public function user_without_permission_cannot_set_own_availability_status(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->patchJson(route('user.check.vacation', $user), [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertForbidden();

        $this->assertDatabaseMissing('vacations', [
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_set_availability_status_of_others(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $target = User::factory()->create();
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $payload = [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ];

        $this->patchJson(route('user.check.vacation', $target), $payload)->assertForbidden();
        $this->patchJson(route('freelancer.check.vacation', $freelancer), $payload)->assertForbidden();
        $this->patchJson(route('service_provider.check.vacation', $serviceProvider), $payload)
            ->assertForbidden();
    }

    /**
     * Selbst erfasste Abwesenheit ist immer „Nicht verfügbar" (NOT_AVAILABLE) – eine wählbare
     * Urlaubsart gibt es im Verfügbarkeitskalender nicht (kommt mit dem Urlaubsmodul); ein trotzdem
     * mitgeschicktes vacation_type wird ignoriert.
     */
    private function ownAbsencePayload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-07-16',
            'type' => 'vacation',
            'full_day' => true,
            'is_series' => false,
            'comment' => 'Test',
        ], $overrides);
    }

    #[Test]
    public function own_absence_is_stored_as_not_available(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('user.vacation.add', $user), $this->ownAbsencePayload())->assertSuccessful();

        $this->assertDatabaseHas('vacations', [
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
            'date' => '2026-07-16',
            'type' => 'NOT_AVAILABLE',
        ]);
    }

    #[Test]
    public function a_sent_vacation_type_is_ignored_for_own_absences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('user.vacation.add', $user), $this->ownAbsencePayload(['vacation_type' => 'OFF_WORK']))
            ->assertSuccessful();

        $this->assertDatabaseHas('vacations', [
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
            'type' => 'NOT_AVAILABLE',
        ]);
        $this->assertDatabaseMissing('vacations', [
            'vacationer_id' => $user->id,
            'vacationer_type' => User::class,
            'type' => 'OFF_WORK',
        ]);
    }

    #[Test]
    public function shift_planner_can_set_availability_status(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $target = User::factory()->create();

        $this->patchJson(route('user.check.vacation', $target), [
            'checked' => ['type' => 'OFF_WORK'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertSuccessful();

        $this->assertDatabaseHas('vacations', [
            'vacationer_id' => $target->id,
            'vacationer_type' => User::class,
            'type' => 'OFF_WORK',
            'comment' => null,
        ]);
    }

    /**
     * Das Status-Dropdown schreibt den Status nicht mehr als Kommentar („NOT_AVAILABLE“) in die
     * Abwesenheit; Dienstplan-Zelle und Freigabe-Anfrage zeigten ihn sonst als Kommentar an.
     */
    #[Test]
    public function changing_the_status_updates_status_entries_without_writing_a_comment(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $target = User::factory()->create();
        $statusEntry = $this->dayVacation($target, ['type' => 'NOT_AVAILABLE', 'comment' => null]);

        $this->changeStatus($target, 'NOT_AVAILABLE', 'OFF_WORK')->assertSuccessful();

        $statusEntry->refresh();
        $this->assertSame('OFF_WORK', $statusEntry->type);
        $this->assertNull($statusEntry->comment);
    }

    #[Test]
    public function changing_the_status_converts_legacy_entries_and_clears_the_type_comment(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $target = User::factory()->create();
        $legacyEntry = $this->dayVacation($target, ['type' => 'NOT_AVAILABLE', 'comment' => 'NOT_AVAILABLE']);

        $this->changeStatus($target, 'NOT_AVAILABLE', 'FREE_WORK')->assertSuccessful();

        $legacyEntry->refresh();
        $this->assertSame('FREE_WORK', $legacyEntry->type);
        $this->assertNull($legacyEntry->comment);
    }

    #[Test]
    public function changing_the_status_keeps_absences_with_own_comment_or_time_window(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $target = User::factory()->create();
        $withComment = $this->dayVacation($target, ['type' => 'NOT_AVAILABLE', 'comment' => 'Arzttermin']);
        $withTimeWindow = $this->dayVacation($target, [
            'type' => 'NOT_AVAILABLE',
            'comment' => null,
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $this->changeStatus($target, 'NOT_AVAILABLE', 'OFF_WORK')->assertSuccessful();

        $this->assertSame('NOT_AVAILABLE', $withComment->refresh()->type);
        $this->assertSame('Arzttermin', $withComment->comment);
        $this->assertSame('NOT_AVAILABLE', $withTimeWindow->refresh()->type);
    }

    #[Test]
    public function migration_removes_status_type_comments_but_keeps_real_comments(): void
    {
        $target = User::factory()->create();
        $legacy = $this->dayVacation($target, ['type' => 'NOT_AVAILABLE', 'comment' => 'NOT_AVAILABLE']);
        $legacyFree = $this->dayVacation($target, ['type' => 'FREE_WORK', 'comment' => 'FREE_WORK', 'date' => '2026-07-17']);
        $real = $this->dayVacation($target, ['type' => 'NOT_AVAILABLE', 'comment' => 'Krank', 'date' => '2026-07-18']);

        (require base_path('database/migrations/2026_11_12_155624_clear_status_type_comments_on_vacations.php'))->up();

        $this->assertNull($legacy->refresh()->comment);
        $this->assertNull($legacyFree->refresh()->comment);
        $this->assertSame('Krank', $real->refresh()->comment);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function dayVacation(User $user, array $attributes): Vacation
    {
        return Vacation::factory()->create(array_merge([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-07-16',
            'full_day' => true,
            'is_series' => false,
        ], $attributes));
    }

    private function changeStatus(User $user, string $from, string $to): \Illuminate\Testing\TestResponse
    {
        return $this->patchJson(route('user.check.vacation', $user), [
            'checked' => ['type' => $to],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => $from],
            'remove_from_shifts' => false,
        ]);
    }

    #[Test]
    public function unknown_availability_status_is_rejected_with_422(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->patchJson(route('user.check.vacation', $user), [
            'checked' => ['type' => 'SOMETHING_ELSE'],
            'day' => '2026-07-16',
            'vacationTypeBeforeUpdate' => ['type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['checked.type']);
    }
}
