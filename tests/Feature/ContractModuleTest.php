<?php

use App\Models\ContractModule;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->contract_module = ContractModule::factory()->create();
});

test('contract modules can be created when a file is provided', function () {

    $this->user->assignRole('admin');

    $this->actingAs($this->user);

    $this->post("/contract_modules", [
        'module' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $this->assertDatabaseHas('contract_modules', [
        'name' => 'document.pdf',
    ]);

});

test('contract modules cannot be created when a file is missing', function () {

    $this->user->assignRole('admin');

    $this->actingAs($this->user);

    $response = $this->post("/contract_modules", [
        'module' => null
    ]);

    $response->assertStatus(400);

});



test('contract modules can be deleted', function() {

    $this->user->assignRole('admin');

    $this->actingAs($this->user);

    $this->delete("/contract_modules/{$this->contract_module->id}");

    //assures that only the fields that were updated changed, and that the rest stays the same.
    $this->assertDatabaseMissing('contract_modules', [
        'id' => $this->contract_module->id,
    ]);

});
