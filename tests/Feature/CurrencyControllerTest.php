<?php

namespace Tests\Feature;

use Artwork\Modules\Currency\Models\Currency;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->adminUser());
    }

    public function testStore(): void
    {
        $name = $this->faker->unique()->word;

        $response = $this->post(route('currencies.store'), ['name' => $name]);

        $response->assertRedirect();
        $this->assertDatabaseHas('currencies', ['name' => $name]);
    }

    public function testDestroy(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->delete(route('currencies.delete', $currency));

        $response->assertRedirect();
        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }

    public function testForceDelete(): void
    {
        $currency = Currency::factory()->create();
        $currency->delete();

        $this->delete(route('currencies.force', $currency->id));

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function testRestore(): void
    {
        $currency = Currency::factory()->create();
        $currency->delete();

        $this->post(route('currencies.restore', $currency->id));

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }
}
