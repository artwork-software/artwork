<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CompanyTypeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('company_types.store'), ['name' => 'X', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('company_types.store'), [
            'name' => 'Vendor',
            'color' => '#abcdef',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('company_types', ['name' => 'Vendor']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $ct = CompanyType::query()->forceCreate(['name' => 'Old', 'color' => '#111']);

        $response = $this->patch(route('company_types.update', $ct), [
            'name' => 'New',
            'color' => '#222',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('company_types', ['id' => $ct->id, 'name' => 'New']);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $ct = CompanyType::query()->forceCreate(['name' => 'X', 'color' => '#fff']);

        $response = $this->delete(route('company_types.delete', $ct));

        $response->assertRedirect();
        $this->assertSoftDeleted('company_types', ['id' => $ct->id]);
    }
}
