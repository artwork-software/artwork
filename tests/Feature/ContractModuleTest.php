<?php

use Artwork\Modules\ContractModule\Models\ContractModule;
use Illuminate\Http\UploadedFile;

beforeEach(function() {
    $this->user = $this->adminUser();
    $this->actingAs($this->user);
    $this->contract_module = ContractModule::factory()->create();
});

test('contract modules can be created when a file is provided', function () {

    $this->post("/contract_modules", [
        'module' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $this->assertDatabaseHas('contract_modules', [
        'name' => 'document.pdf',
    ]);

});

test('contract modules cannot be created when a file is missing', function () {

    $response = $this->post("/contract_modules", [
        'module' => null
    ]);

    $response->assertStatus(400);

});



test('contract modules can be deleted', function() {

    $this->delete("/contract_modules/{$this->contract_module->id}");

    //assures that only the fields that were updated changed, and that the rest stays the same.
    $this->assertDatabaseMissing('contract_modules', [
        'id' => $this->contract_module->id,
    ]);

});
