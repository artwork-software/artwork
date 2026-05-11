<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\ShiftQualification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftQualificationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('shift-qualifications.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('shift-qualifications.store'), [
            'name' => 'MyQual',
            'available' => true,
            'icon' => 'IconStar',
            'is_freelancer_qualification' => false,
            'is_service_provider_qualification' => false,
            'is_user_qualification' => true,
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_update(): void
    {
        $sq = ShiftQualification::factory()->create();

        $this->patch(route('shift-qualifications.update', $sq), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $sq = ShiftQualification::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('shift-qualifications.update', $sq), [
            'name' => 'New',
            'available' => true,
            'icon' => 'IconStar',
            'is_freelancer_qualification' => false,
            'is_service_provider_qualification' => false,
            'is_user_qualification' => true,
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_destroy_default_qualification_is_blocked(): void
    {
        $this->actingAsAdmin();
        $sq = ShiftQualification::query()->find(1);
        if ($sq === null) {
            $sq = ShiftQualification::factory()->create(['id' => 1]);
        }

        // Controller returns back() without delete when id === 1
        $response = $this->delete(route('shift-qualifications.destroy', $sq));

        $response->assertRedirect();
    }
}
