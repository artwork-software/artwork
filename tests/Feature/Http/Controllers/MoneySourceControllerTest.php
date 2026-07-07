<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_money_sources_index(): void
    {
        $this->get(route('money_sources.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_money_sources_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('money_sources.index'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_view_money_sources_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('money_sources.settings'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_store_money_source(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('money_sources.store'), [
            'name' => 'New Source',
            'amount' => '5000',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'source_name' => 'Bank',
            'description' => 'Some desc',
            'is_group' => false,
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('money_sources', ['name' => 'New Source']);
    }

    #[Test]
    public function admin_can_destroy_money_source(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->delete('/money_sources/' . $source->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('money_sources', ['id' => $source->id]);
    }

    #[Test]
    public function admin_can_pin_money_source(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(route('money_sources.pin', $source));

        $response->assertRedirect();
    }
}
