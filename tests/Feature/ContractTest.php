<?php

use App\Models\Contract;
use App\Models\User;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Http\UploadedFile;

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->contract = Contract::factory()->create();
});

test('contracts can be created with the correct variables', function () {

    $this->user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);

    $this->actingAs($this->user);

     $this->post("/projects/{$this->project->id}/contracts", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
        'contract_partner' => 'Agentur XYZ',
        'amount' => 2000,
        'description' => 'Test description',
        'ksk_liable' => true,
        'resident_abroad' => true,
    ]);

    $this->assertDatabaseHas('contracts', [
        'name' => 'document.pdf',
        'project_id' => $this->project->id
    ]);

});

//@todo method not allowed, is there a way to update contracts?

//test('contracts can be updated', function() {
//
//    $this->user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
//    $this->project->contracts()->save($this->contract);
//
//    $this->actingAs($this->user);
//
//    $this->patch("/contracts/{$this->contract->id}", [
//        'contract' => UploadedFile::fake()->create('document2.pdf', 100),
//        'contract_partner' => 'Agentur Hamburg',
//        'amount' => 2000,
//        'description' => 'Test description',
//    ]);
//
//    //assures that only the fields that were updated changed, and that the rest stays the same.
//    $this->assertDatabaseHas('contracts', [
//        'name' => 'document2.pdf',
//        'project_id' => $this->project->id,
//        'contract_partner' => 'Agentur Hamburg',
//        'amount' => 2000,
//        'resident_abroad' => false
//    ]);
//
//});

test('contracts can be deleted', function() {

    $this->user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
    $this->project->contracts()->save($this->contract);

    $this->actingAs($this->user);

    $this->delete("/contracts/{$this->contract->id}");

    //assures that only the fields that were updated changed, and that the rest stays the same.
    $this->assertDatabaseMissing('contracts', [
        'id' => $this->contract->id,
    ]);

});
