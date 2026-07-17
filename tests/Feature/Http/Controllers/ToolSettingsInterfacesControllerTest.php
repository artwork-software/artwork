<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ToolSettingsInterfacesControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_interfaces(): void
    {
        $this->get(route('tool.interfaces'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_interfaces(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tool.interfaces'));

        $response->assertOk();
    }

    #[Test]
    public function sage_mutations_require_settings_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->post(route('tool.interfaces.sage.initialize'))->assertForbidden();
        $this->post(route('tool.interfaces.sage.initializeSpecificDay'), [
            'specificDayFrom' => '2026-07-01',
        ])->assertForbidden();
        $this->delete(route('tool.interfaces.sage.delete'))->assertForbidden();
        $this->post(route('tool.interfaces.sage.deleteBookingDays'), [
            'dateFrom' => '2026-07-01',
        ])->assertForbidden();
    }

    #[Test]
    public function sage_specific_import_rejects_unsafe_ktr_values_and_reversed_ranges(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->from(route('tool.interfaces'))
            ->post(route('tool.interfaces.sage.initializeSpecificDay'), ['ktr' => "100'; DROP TABLE users;--"])
            ->assertRedirect(route('tool.interfaces'))
            ->assertSessionHasErrors('ktr');

        $this->from(route('tool.interfaces'))
            ->post(route('tool.interfaces.sage.initializeSpecificDay'), [
                'specificDayFrom' => '2026-07-10',
                'specificDayTo' => '2026-07-01',
            ])
            ->assertRedirect(route('tool.interfaces'))
            ->assertSessionHasErrors('specificDayTo');
    }

    #[Test]
    public function sage_booking_delete_rejects_unsafe_ktr_values_and_reversed_ranges(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->post(route('tool.interfaces.sage.deleteBookingDays'), ['ktr' => '42 OR 1=1'])
            ->assertSessionHasErrors('ktr');

        $this->post(route('tool.interfaces.sage.deleteBookingDays'), [
            'dateFrom' => '2026-07-10',
            'dateTo' => '2026-07-01',
        ])->assertSessionHasErrors('dateTo');
    }
}
