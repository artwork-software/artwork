<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessRepository;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessScopeRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RepositoryTest extends TestCase
{
    private function repo(): ExternalAccessRepository
    {
        return app(ExternalAccessRepository::class);
    }

    private function scope(ExternalAccess $access, Carbon $validTo): ExternalAccessScope
    {
        return ExternalAccessScope::create([
            'external_access_id' => $access->id,
            'project_id' => Project::factory()->create()->id,
            'project_tab_id' => ProjectTab::factory()->create()->id,
            'access_type' => ExternalAccessType::READ,
            'valid_from' => now()->subDay(),
            'valid_to' => $validTo,
        ]);
    }

    #[Test]
    public function find_all_for_contact_returns_all_including_revoked(): void
    {
        $access = ExternalAccess::factory()->create();
        $contact = $access->crmContact;
        ExternalAccess::factory()->create(['crm_contact_id' => $contact->id, 'revoked_at' => now()]);

        $result = $this->repo()->findAllForContact($contact);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function find_global_index_filters_by_active_status(): void
    {
        $active = ExternalAccess::factory()->create(['revoked_at' => null, 'crm_access_expires_at' => now()->addMonth()]);
        ExternalAccess::factory()->create(['revoked_at' => now()]);
        ExternalAccess::factory()->create(['revoked_at' => null, 'crm_access_expires_at' => now()->subDay()]);

        $result = $this->repo()->findGlobalIndex('active');

        $ids = $result->getCollection()->pluck('id')->all();
        $this->assertContains($active->id, $ids);
        $this->assertCount(1, $ids);
    }

    #[Test]
    public function find_global_index_paginates(): void
    {
        ExternalAccess::factory()->count(3)->create();

        $result = $this->repo()->findGlobalIndex(null, 2);

        $this->assertSame(2, $result->perPage());
        $this->assertTrue($result->total() >= 3);
    }

    #[Test]
    public function find_by_access_orders_by_valid_to_desc(): void
    {
        $access = ExternalAccess::factory()->create();
        $older = $this->scope($access, now()->addDays(5));
        $newer = $this->scope($access, now()->addDays(30));

        $result = app(ExternalAccessScopeRepository::class)->findByAccess($access);

        $this->assertSame($newer->id, $result->first()->id);
        $this->assertSame($older->id, $result->last()->id);
    }
}
