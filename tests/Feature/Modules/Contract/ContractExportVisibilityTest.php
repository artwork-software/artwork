<?php

namespace Tests\Feature\Modules\Contract;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Services\ContractService;
use Artwork\Modules\Department\Models\Department;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractExportVisibilityTest extends FeatureTestCase
{
    #[Test]
    public function export_contains_the_same_contracts_as_the_list_for_a_regular_user(): void
    {
        $user = $this->actingAsUserWith([]);
        $department = Department::factory()->create();
        $department->users()->attach($user->id);

        $own = Contract::factory()->create(['creator_id' => $user->id]);
        $sharedPersonally = Contract::factory()->create();
        $sharedPersonally->accessingUsers()->attach($user->id);
        $sharedWithTeam = Contract::factory()->create();
        $sharedWithTeam->accessingDepartments()->attach($department->id);
        $foreign = Contract::factory()->create();

        $exported = app(ContractService::class)->contractsForExport([], $user->fresh())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$own->id, $sharedPersonally->id, $sharedWithTeam->id], $exported);
        $this->assertNotContains($foreign->id, $exported);

        $listed = collect($this->get(route('contracts.index'))->assertOk()->viewData('page')['props']['contracts'])
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing($exported, $listed);
    }

    #[Test]
    public function export_contains_all_contracts_for_admins_and_keeps_the_filters(): void
    {
        $admin = $this->actingAsAdmin();
        $kskLiable = Contract::factory()->create(['ksk_liable' => true]);
        $notKskLiable = Contract::factory()->create(['ksk_liable' => false]);

        $service = app(ContractService::class);
        $all = $service->contractsForExport([], $admin)->pluck('id')->all();
        $filtered = $service->contractsForExport(['kskLiable' => true], $admin)->pluck('id')->all();

        $this->assertContains($kskLiable->id, $all);
        $this->assertContains($notKskLiable->id, $all);
        $this->assertContains($kskLiable->id, $filtered);
        $this->assertNotContains($notKskLiable->id, $filtered);
    }
}
