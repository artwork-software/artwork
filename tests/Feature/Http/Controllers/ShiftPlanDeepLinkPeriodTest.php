<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Enums\UserFilterTypes;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Härtung: Deep-Link-Zeiträume (start_date/end_date in der URL, aus Benachrichtigungen) werden
 * für den Request angezeigt, aber NICHT mehr in user_filters bzw. im Einsatzplan-Filter gespeichert.
 */
final class ShiftPlanDeepLinkPeriodTest extends FeatureTestCase
{
    #[Test]
    public function shift_plan_deep_link_period_is_displayed_but_not_persisted(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_FILTER->value],
            ['start_date' => '2026-03-02', 'end_date' => '2026-03-08']
        );

        $response = $this->get(route('shifts.plan', ['start_date' => '2026-06-08', 'end_date' => '2026-06-14']));

        $response->assertOk();
        $this->assertSame(['2026-06-08', '2026-06-14'], $response->inertiaProps('dateValue'));

        $stored = $admin->userFilters()->where('filter_type', UserFilterTypes::SHIFT_FILTER->value)->first();
        $this->assertSame('2026-03-02', $stored->start_date->toDateString());
        $this->assertSame('2026-03-08', $stored->end_date->toDateString());
    }

    #[Test]
    public function shift_plan_without_query_keeps_using_the_stored_period(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_FILTER->value],
            ['start_date' => '2026-03-02', 'end_date' => '2026-03-08']
        );

        $response = $this->get(route('shifts.plan'));

        $response->assertOk();
        $this->assertSame(['2026-03-02', '2026-03-08'], $response->inertiaProps('dateValue'));
    }

    #[Test]
    public function operation_plan_deep_link_period_is_displayed_but_not_persisted(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->workerShiftPlanFilter()->create([
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-08',
        ]);

        $response = $this->get(route('user.edit.shiftplan', [
            'user' => $admin->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-14',
        ]));

        $response->assertOk();
        $this->assertSame(['2026-06-08', '2026-06-14'], $response->inertiaProps('dateValue'));

        $stored = $admin->workerShiftPlanFilter()->first();
        $this->assertSame('2026-03-02', $stored->start_date->toDateString());
        $this->assertSame('2026-03-08', $stored->end_date->toDateString());
    }
}
