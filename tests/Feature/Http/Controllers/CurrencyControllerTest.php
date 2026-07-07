<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CurrencyControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_currency(): void
    {
        $this->post(route('currencies.store'), ['name' => 'USD', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_currency(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('currencies.store'), [
            'name' => 'Euro',
            'color' => '#0066cc',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('currencies', ['name' => 'Euro']);
    }

    #[Test]
    public function admin_can_update_currency(): void
    {
        $this->actingAsAdmin();
        $currency = Currency::query()->forceCreate([
            'name' => 'Pound',
            'color' => '#000',
        ]);

        $response = $this->patch(route('currencies.update', $currency), [
            'name' => 'British Pound',
            'color' => '#fff',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'British Pound',
        ]);
    }

    #[Test]
    public function admin_can_destroy_currency(): void
    {
        $this->actingAsAdmin();
        $currency = Currency::query()->forceCreate([
            'name' => 'Yen',
            'color' => '#aaa',
        ]);

        $response = $this->delete(route('currencies.delete', $currency));

        $response->assertRedirect();
        // SoftDeletes — record stays but deleted_at set
        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }
}
