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
    public function category_without_a_name_is_rejected_with_a_message(): void
    {
        $this->actingAsAdmin();
        $countBefore = MoneySourceCategory::count();

        $this->post(route('money_source_categories.store'), ['name' => '   '])
            ->assertSessionHasErrors(['name' => __('Please enter a name for the source category.')]);

        $this->assertSame($countBefore, MoneySourceCategory::count());
    }

    #[Test]
    public function category_with_an_existing_name_is_rejected_with_a_message(): void
    {
        $this->actingAsAdmin();
        MoneySourceCategory::create(['name' => 'Förderung']);

        $this->post(route('money_source_categories.store'), ['name' => 'Förderung'])
            ->assertSessionHasErrors(['name' => __('A source category with this name already exists.')]);

        $this->assertSame(1, MoneySourceCategory::where('name', 'Förderung')->count());
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
