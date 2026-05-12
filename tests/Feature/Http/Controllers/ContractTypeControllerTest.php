<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\ContractType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractTypeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_contract_types_index(): void
    {
        $this->get(route('contract_types.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_contract_types_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('contract_types.index'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_store_contract_type(): void
    {
        $this->post(route('contract_types.store'), ['name' => 'Type', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_contract_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('contract_types.store'), [
            'name' => 'NDA',
            'color' => '#abc',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_types', ['name' => 'NDA']);
    }

    #[Test]
    public function admin_can_update_contract_type(): void
    {
        $this->actingAsAdmin();
        $type = ContractType::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('contract_types.update', $type), [
            'name' => 'NewName',
            'color' => '#000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_types', ['id' => $type->id, 'name' => 'NewName']);
    }

    #[Test]
    public function admin_can_destroy_contract_type(): void
    {
        $this->actingAsAdmin();
        $type = ContractType::factory()->create();

        $response = $this->delete(route('contract_types.delete', $type));

        $response->assertRedirect();
        $this->assertSoftDeleted('contract_types', ['id' => $type->id]);
    }

    #[Test]
    public function admin_can_restore_contract_type(): void
    {
        $this->actingAsAdmin();
        $type = ContractType::factory()->create();
        $type->delete();

        $response = $this->patch(route('contract_types.restore', $type->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_types', ['id' => $type->id, 'deleted_at' => null]);
    }
}
