<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\ContractModule;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractModuleControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy_contract_module(): void
    {
        $module = ContractModule::create(['name' => 'Test', 'basename' => 'test']);

        $this->delete('/contract_modules/' . $module->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_contract_module(): void
    {
        $this->actingAsAdmin();
        $module = ContractModule::create(['name' => 'Test', 'basename' => 'test']);

        $response = $this->delete('/contract_modules/' . $module->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('contract_modules', ['id' => $module->id]);
    }
}
