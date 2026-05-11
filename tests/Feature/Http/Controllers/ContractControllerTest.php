<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_contracts_index(): void
    {
        $this->get(route('contracts.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_contracts_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('contracts.index'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_save_filter(): void
    {
        $this->post(route('contracts.filter.save'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_save_filter(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('contracts.filter.save'), [
            'kskLiable' => true,
            'foreignTax' => false,
            'dateFrom' => null,
            'dateTo' => null,
            'legalFormIds' => [],
            'contractTypeIds' => [],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
