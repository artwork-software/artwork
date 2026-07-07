<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\SumMoneySource;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SumDetailsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_sum_details(): void
    {
        $this->get(route('project.budget.sum-details.show'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_sum_details_with_no_data(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('project.budget.sum-details.show'));

        $response->assertOk();
        $response->assertJsonStructure(['sumDetail']);
    }

    #[Test]
    public function guest_cannot_destroy_sum_money_source(): void
    {
        $sumMoneySource = SumMoneySource::factory()->create();

        $this->delete(route('project.sum.money.source.destroy', $sumMoneySource))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_sum_money_source(): void
    {
        $this->actingAsAdmin();
        $sumMoneySource = SumMoneySource::factory()->create();

        $response = $this->delete(route('project.sum.money.source.destroy', $sumMoneySource));

        $response->assertRedirect();
        $this->assertSoftDeleted('sum_money_sources', ['id' => $sumMoneySource->id]);
    }
}
