<?php

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->contract = Contract::factory()->create();
});

test('contracts can be created with the correct variables', function (): void {

    $this->user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

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

test('contracts can be updated', function (): void {

    $this->user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->project->contracts()->save($this->contract);

    $this->actingAs($this->user);

    $this->patch("/contracts/{$this->contract->id}", [
        'file' => UploadedFile::fake()->create('document2.pdf', 100),
        'contract_partner' => 'Agentur Hamburg',
        'amount' => 2000,
        'description' => 'Test description',
    ]);
   //assures that only the fields that were updated changed, and that the rest stays the same.
    $this->assertDatabaseHas('contracts', [
        'name' => 'document2.pdf',
        'project_id' => $this->project->id,
        'contract_partner' => 'Agentur Hamburg',
        'amount' => 2000,
        'description' => 'Test description',
    ]);
});

test('contracts can be deleted', function (): void {

    $this->user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->project->contracts()->save($this->contract);

    $this->actingAs($this->user);

    $this->delete("/contracts/{$this->contract->id}");

    //assures that only the fields that were updated changed, and that the rest stays the same.
    $this->assertDatabaseMissing('contracts', [
        'id' => $this->contract->id,
    ]);
});
