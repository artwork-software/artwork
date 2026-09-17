<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessScopeRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ScopePerProjectTest extends TestCase
{
    #[Test]
    public function same_tab_in_two_projects_creates_two_scopes(): void
    {
        $repo = app(ExternalAccessScopeRepository::class);
        $external = ExternalAccess::factory()->active()->create();
        $tab = ProjectTab::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $repo->addOrUpdateScope($external, $projectA->id, $tab->id, ExternalAccessType::READ, now(), now()->addMonth(), null);
        $repo->addOrUpdateScope($external, $projectB->id, $tab->id, ExternalAccessType::WRITE, now(), now()->addMonth(), null);

        $this->assertSame(2, $external->scopes()->count());
        $this->assertSame(
            [$projectA->id, $projectB->id],
            $external->scopes()->orderBy('id')->pluck('project_id')->all(),
        );
    }

    #[Test]
    public function same_tab_in_same_project_updates_existing_scope(): void
    {
        $repo = app(ExternalAccessScopeRepository::class);
        $external = ExternalAccess::factory()->active()->create();
        $tab = ProjectTab::factory()->create();
        $project = Project::factory()->create();

        $first = $repo->addOrUpdateScope($external, $project->id, $tab->id, ExternalAccessType::READ, now(), now()->addMonth(), null);
        $first->forceFill(['expiry_reminder_sent_at' => now()])->save();
        $second = $repo->addOrUpdateScope($external, $project->id, $tab->id, ExternalAccessType::WRITE, now(), now()->addMonths(2), null);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $external->scopes()->count());
        $this->assertSame(ExternalAccessType::WRITE, $second->fresh()->access_type);
        // erneute Einladung = neue Laufzeit → Ablauf-Erinnerung darf erneut ausgelöst werden
        $this->assertNull($second->fresh()->expiry_reminder_sent_at);
    }
}
