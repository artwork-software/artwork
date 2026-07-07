<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceCategoryControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_money_source_category(): void
    {
        $this->post(route('money_source_categories.store'), ['name' => 'Cat'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_money_source_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('money_source_categories.store'), ['name' => 'Funding']);

        $response->assertRedirect();
        $this->assertDatabaseHas('money_source_categories', ['name' => 'Funding']);
    }

    #[Test]
    public function admin_can_destroy_money_source_category(): void
    {
        $this->actingAsAdmin();
        $category = MoneySourceCategory::create(['name' => 'X']);

        $response = $this->delete(route('money_source_categories.destroy', $category));

        $response->assertRedirect();
        $this->assertDatabaseMissing('money_source_categories', ['id' => $category->id]);
    }
}
