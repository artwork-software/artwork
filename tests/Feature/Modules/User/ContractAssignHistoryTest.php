<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Jobs\RevalidateShiftRulesJob;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 4c: Vertragszuweisung als Historie (user_contract_assigns.valid_from/valid_until),
 * Tab "Vertrag & Arbeitszeit", Resolver mit Stichtag.
 */
final class ContractAssignHistoryTest extends FeatureTestCase
{
    private function template(string $name, int $compensationPeriod): UserContract
    {
        return UserContract::factory()->create([
            'name' => $name,
            'compensation_period' => $compensationPeriod,
            'special_day_rule_active' => true,
        ]);
    }

    /**
     * @return array{0: User, 1: UserContractAssign, 2: UserContract}
     */
    private function userWithOpenAssign(int $compensationPeriod = 30, ?string $validFrom = null): array
    {
        $user = User::factory()->create();
        $template = $this->template('Alt', $compensationPeriod);
        // Zuweisung spiegelt die Vorlagenwerte (wie bei der Auswahl im UI) – keine zufälligen Abweichungen
        $assign = UserContractAssign::factory()->create(array_merge(
            $this->payloadFromTemplate($template),
            [
                'user_id' => $user->id,
                'compensation_period' => $compensationPeriod,
                'special_day_rule_active' => true,
                'valid_from' => $validFrom,
                'valid_until' => null,
            ]
        ));

        return [$user, $assign, $template];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromTemplate(UserContract $template, array $overrides = []): array
    {
        return array_merge([
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => $template->free_full_days_per_week,
            'free_half_days_per_week' => $template->free_half_days_per_week,
            'special_day_rule_active' => (bool) $template->special_day_rule_active,
            'compensation_period' => $template->compensation_period,
            'free_sundays_per_season' => $template->free_sundays_per_season,
            'days_off_first_26_weeks' => $template->days_off_first_26_weeks,
        ], $overrides);
    }

    #[Test]
    public function planning_a_change_closes_the_previous_open_period_the_day_before(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $old] = $this->userWithOpenAssign(30);
        $new = $this->template('Neu', 60);
        $from = Carbon::today()->addDays(10);

        $this->patch(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($new, ['valid_from' => $from->toDateString()])
        )->assertRedirect()->assertSessionHas('success');

        $this->assertSame($from->copy()->subDay()->toDateString(), $old->fresh()->valid_until?->toDateString());
        $this->assertNull($old->fresh()->valid_from);

        $created = $user->contractAssigns()->where('user_contract_id', $new->id)->first();
        $this->assertNotNull($created);
        $this->assertSame($from->toDateString(), $created->valid_from->toDateString());
        $this->assertNull($created->valid_until);
        $this->assertSame(2, $user->contractAssigns()->count());
    }

    #[Test]
    public function contract_assign_for_resolves_the_period_valid_on_a_date(): void
    {
        [$user, $old] = $this->userWithOpenAssign(30);
        $new = $this->template('Neu', 60);
        $switch = Carbon::today()->addDays(10);
        $old->update(['valid_until' => $switch->copy()->subDay()]);
        $created = UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'user_contract_id' => $new->id,
            'compensation_period' => 60,
            'valid_from' => $switch,
            'valid_until' => null,
        ]);

        $user = $user->fresh();

        $this->assertSame($old->id, $user->contractAssignFor(Carbon::today())?->id);
        $this->assertSame($old->id, $user->contractAssignFor($switch->copy()->subDay())?->id);
        $this->assertSame($created->id, $user->contractAssignFor($switch)?->id);
        $this->assertSame($created->id, $user->contractAssignFor($switch->copy()->addYear())?->id);

        // Abwärtskompatibel: contract (HasOne) = heute gültiger Satz, auch per Eager-Load
        $this->assertSame($old->id, $user->contract?->id);
        $this->assertSame($old->id, $user->contract()->first()?->id);
        $this->assertSame($old->id, User::query()->with('contract')->find($user->id)->contract?->id);
        $this->assertTrue(User::query()->whereHas('contract')->whereKey($user->id)->exists());
        $this->assertSame($old->userContract->id, $user->activeWorkContract()?->id);
        $this->assertSame($new->id, $user->activeWorkContract($switch)?->id);
    }

    #[Test]
    public function contract_relation_prefers_the_youngest_period_on_overlap(): void
    {
        [$user, $old] = $this->userWithOpenAssign(30);
        $younger = UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'user_contract_id' => $this->template('Jung', 45)->id,
            'valid_from' => Carbon::today()->subDays(3),
            'valid_until' => null,
        ]);

        $user = $user->fresh();

        $this->assertSame($younger->id, $user->contract?->id);
        $this->assertSame($younger->id, $user->contractAssignFor()?->id);
        $this->assertSame($old->id, $user->contractAssignFor(Carbon::today()->subDays(4))?->id);
    }

    #[Test]
    public function resolver_with_date_returns_the_values_of_the_matching_period(): void
    {
        [$user, $old] = $this->userWithOpenAssign(30);
        $switch = Carbon::today()->addDays(10);
        $old->update(['valid_until' => $switch->copy()->subDay()]);
        UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'user_contract_id' => $this->template('Neu', 60)->id,
            'compensation_period' => 60,
            'special_day_rule_active' => false,
            'valid_from' => $switch,
            'valid_until' => null,
        ]);

        $resolver = app(ContractSettingsResolver::class);
        $user = $user->fresh();

        $this->assertSame(30, $resolver->compensationPeriod($user));
        $this->assertSame(30, $resolver->compensationPeriod($user, Carbon::today()));
        $this->assertSame(60, $resolver->compensationPeriod($user, $switch));
        $this->assertTrue($resolver->bool($user, 'special_day_rule_active', true));
        $this->assertFalse($resolver->bool($user, 'special_day_rule_active', true, $switch->copy()->addDay()));
        $this->assertSame(60, $resolver->int($user, 'compensation_period', 0, $switch->copy()->addMonth()));
    }

    #[Test]
    public function overlapping_periods_are_rejected_with_422(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();
        $bounded = $this->template('Befristet', 30);
        UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'user_contract_id' => $bounded->id,
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-12-31',
        ]);
        $new = $this->template('Neu', 60);

        $this->patchJson(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($new, ['valid_from' => '2026-06-01'])
        )->assertStatus(422)->assertJsonValidationErrors(['valid_from']);

        $this->assertSame(1, $user->contractAssigns()->count());
    }

    #[Test]
    public function valid_until_before_valid_from_is_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();
        $new = $this->template('Neu', 60);

        $this->patchJson(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($new, ['valid_from' => '2026-06-01', 'valid_until' => '2026-05-01'])
        )->assertStatus(422)->assertJsonValidationErrors(['valid_until']);
    }

    #[Test]
    public function an_existing_period_can_be_edited_via_assign_id(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign, $template] = $this->userWithOpenAssign(30, '2026-01-01');

        $this->patch(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($template, [
                'assign_id' => $assign->id,
                'compensation_period' => 45,
                'valid_from' => '2026-02-01',
                'valid_until' => '2026-12-31',
            ])
        )->assertRedirect()->assertSessionHas('success');

        $assign = $assign->fresh();
        $this->assertSame(45, $assign->compensation_period);
        $this->assertSame('2026-02-01', $assign->valid_from->toDateString());
        $this->assertSame('2026-12-31', $assign->valid_until->toDateString());
        $this->assertSame(1, $user->contractAssigns()->count());
    }

    #[Test]
    public function legacy_request_without_validity_updates_the_current_period(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign, $template] = $this->userWithOpenAssign(30);

        $this->patch(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($template, ['compensation_period' => 99])
        )->assertRedirect()->assertSessionHas('success');

        $this->assertSame(99, $assign->fresh()->compensation_period);
        $this->assertNull($assign->fresh()->valid_from);
        $this->assertSame(1, $user->contractAssigns()->count());
    }

    #[Test]
    public function a_period_can_be_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign] = $this->userWithOpenAssign(30, '2026-01-01');

        $this->delete(route('user-contract-settings.assign.destroy', ['user' => $user, 'assign' => $assign]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_contract_assigns', ['id' => $assign->id]);
    }

    #[Test]
    public function a_period_of_another_person_cannot_be_deleted_through_a_foreign_user(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign] = $this->userWithOpenAssign(30, '2026-01-01');
        $other = User::factory()->create();

        $this->delete(route('user-contract-settings.assign.destroy', ['user' => $other, 'assign' => $assign]))
            ->assertNotFound();

        $this->assertDatabaseHas('user_contract_assigns', ['id' => $assign->id]);
    }

    #[Test]
    public function retroactive_periods_flash_a_hint_and_revalidate_from_their_start(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();
        $new = $this->template('Neu', 60);
        $from = Carbon::today()->subDays(20);

        $response = $this->patch(
            route('user-contract-settings.update-user', $user),
            $this->payloadFromTemplate($new, ['valid_from' => $from->toDateString()])
        )->assertRedirect();

        $this->assertStringContainsString('re-checked', (string) $response->getSession()->get('success'));

        Bus::assertDispatched(
            RevalidateShiftRulesJob::class,
            fn (RevalidateShiftRulesJob $job): bool => $job->userIds === [$user->id]
                && $job->from === $from->toDateString()
        );
    }

    #[Test]
    public function the_page_requires_manage_workers_permission(): void
    {
        $user = User::factory()->create();
        [$target, $assign] = $this->userWithOpenAssign(30, '2026-01-01');
        $this->actingAs($user);

        $this->get(route('user.edit.contract-and-work-time', $target))->assertForbidden();
        $this->patch(route('user-contract-settings.update-user', $target), ['compensation_period' => 5])
            ->assertForbidden();
        $this->patch(route('shift.work-time-pattern.update-user', $target), ['monday' => '08:00'])
            ->assertForbidden();
        $this->delete(route('user-contract-settings.assign.destroy', ['user' => $target, 'assign' => $assign]))
            ->assertForbidden();
    }

    #[Test]
    public function the_page_lists_the_history_with_template_names_and_deviations(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign, $template] = $this->userWithOpenAssign(30, '2026-01-01');
        $assign->update(['compensation_period' => 45]);

        $response = $this->get(route('user.edit.contract-and-work-time', $user))->assertOk();

        $this->assertInertiaComponent($response, 'Users/UserContractWorkTimePage');
        $response->assertInertia(fn ($page) => $page
            ->where('currentTab', 'contractAndWorkTime')
            ->has('today')
            ->has('contractAssigns', 1)
            ->where('contractAssigns.0.contract_name', $template->name)
            ->where('contractAssigns.0.valid_from', '2026-01-01')
            ->where('contractAssigns.0.deviations.0.key', 'compensation_period')
            ->where('contractAssigns.0.deviations.0.value', 45)
            ->has('workTimes')
            ->has('userContracts')
            ->has('workTimePatterns'));
    }

    #[Test]
    public function a_zero_on_the_period_inherits_the_template_value_and_is_no_deviation(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign] = $this->userWithOpenAssign(90, '2026-01-01');
        // 0 auf der Zuweisung = nicht gesetzt (ContractSettingsResolver::ZERO_MEANS_UNSET_ON_ASSIGN)
        $assign->update(['compensation_period' => 0]);

        $response = $this->get(route('user.edit.contract-and-work-time', $user))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has('contractAssigns', 1)
            ->where('contractAssigns.0.deviations', [])
            ->where('contractAssigns.0.effective_values.compensation_period.value', 90)
            ->where('contractAssigns.0.effective_values.compensation_period.inherited', true));

        // Echter Wert auf der Zuweisung: Abweichung gelistet, wirksamer Wert = Zuweisung
        $assign->update(['compensation_period' => 30]);

        $response = $this->get(route('user.edit.contract-and-work-time', $user))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where('contractAssigns.0.deviations.0.key', 'compensation_period')
            ->where('contractAssigns.0.deviations.0.value', 30)
            ->where('contractAssigns.0.deviations.0.template_value', 90)
            ->where('contractAssigns.0.effective_values.compensation_period.value', 30)
            ->where('contractAssigns.0.effective_values.compensation_period.inherited', false));
    }

    #[Test]
    public function old_routes_redirect_to_the_combined_tab(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create();

        $this->get(route('user.edit.contract', $user))
            ->assertRedirect(route('user.edit.contract-and-work-time', $user));
        $this->get(route('user.edit.work-time-pattern', $user))
            ->assertRedirect(route('user.edit.contract-and-work-time', $user));
    }

    #[Test]
    public function updating_a_work_time_pattern_without_validity_keeps_its_validity(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user] = $this->userWithOpenAssign(30);
        $workTime = $user->workTimes()->create([
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'saturday' => '00:00',
            'sunday' => '00:00',
            'valid_from' => '2026-02-01',
            'valid_until' => '2026-12-31',
        ]);

        // Muster-Update per id OHNE valid_from/valid_until → Gültigkeit bleibt (vorher: heute / null)
        $this->patch(route('shift.work-time-pattern.update-user', $user), [
            'id' => $workTime->id,
            'monday' => '06:00',
            'friday' => '04:00',
        ])->assertRedirect()->assertSessionHas('success');

        $workTime->refresh();
        $this->assertSame('06:00', $workTime->monday->format('H:i'));
        $this->assertSame('04:00', $workTime->friday->format('H:i'));
        $this->assertSame('2026-02-01', $workTime->valid_from->toDateString());
        $this->assertSame('2026-12-31', $workTime->valid_until->toDateString());
        $this->assertSame(1, $user->workTimes()->count());

        // Mit Gültigkeit in der Anfrage wird sie weiterhin übernommen
        $this->patch(route('shift.work-time-pattern.update-user', $user), [
            'id' => $workTime->id,
            'monday' => '06:00',
            'valid_from' => '2026-03-01',
            'valid_until' => null,
        ])->assertRedirect()->assertSessionHas('success');

        $workTime->refresh();
        $this->assertSame('2026-03-01', $workTime->valid_from->toDateString());
        $this->assertNull($workTime->valid_until);
    }

    #[Test]
    public function work_time_endpoint_with_validity_does_not_touch_the_contract_history(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        [$user, $assign] = $this->userWithOpenAssign(30);
        $from = Carbon::today()->addDays(5)->toDateString();

        $this->patch(route('shift.work-time-pattern.update-user', $user), [
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '06:00',
            'saturday' => '00:00',
            'sunday' => '00:00',
            'valid_from' => $from,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, $user->contractAssigns()->count());
        $this->assertNull($assign->fresh()->valid_until);
        $this->assertDatabaseHas('user_work_times', ['user_id' => $user->id, 'valid_from' => $from]);
    }
}
