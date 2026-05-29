<?php

namespace Tests\Feature\ExternalAccess\Management;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessManagementService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class ServiceTest extends TestCase
{
    private function service(): ExternalAccessManagementService
    {
        return app(ExternalAccessManagementService::class);
    }

    private function scope(ExternalAccess $access): ExternalAccessScope
    {
        return ExternalAccessScope::create([
            'external_access_id' => $access->id,
            'project_id' => Project::factory()->create()->id,
            'project_tab_id' => ProjectTab::factory()->create()->id,
            'access_type' => ExternalAccessType::READ,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDays(10),
        ]);
    }

    #[Test]
    public function extend_crm_access_updates_expiry_and_logs(): void
    {
        $actor = User::factory()->create();
        $access = ExternalAccess::factory()->create(['crm_access_expires_at' => now()->addDay()]);
        $new = Carbon::now()->addYear()->startOfSecond();

        $this->service()->extendCrmAccess($access, $new, $actor);

        $this->assertSame($new->format('Y-m-d H:i:s'), $access->fresh()->crm_access_expires_at->format('Y-m-d H:i:s'));
        $this->assertTrue(
            Activity::query()->where('log_name', 'external_access_management')
                ->where('description', 'crm_access_extended')->exists()
        );
    }

    #[Test]
    public function extend_crm_access_rejects_past_dates(): void
    {
        $this->expectException(\DomainException::class);
        $this->service()->extendCrmAccess(ExternalAccess::factory()->create(), Carbon::now()->subDay(), User::factory()->create());
    }

    #[Test]
    public function revoke_sets_revoked_at_and_is_idempotent(): void
    {
        $actor = User::factory()->create();
        $access = ExternalAccess::factory()->create(['revoked_at' => null]);

        $this->service()->revoke($access, $actor, 'gone');
        $first = $access->fresh()->revoked_at;
        $this->assertNotNull($first);

        $this->service()->revoke($access->fresh(), $actor);
        $this->assertEquals($first, $access->fresh()->revoked_at); // unchanged
    }

    #[Test]
    public function reactivate_clears_revoked_at_but_does_not_extend_expiry(): void
    {
        $actor = User::factory()->create();
        $expiry = now()->subDay();
        $access = ExternalAccess::factory()->create(['revoked_at' => now(), 'crm_access_expires_at' => $expiry]);

        $this->service()->reactivate($access, $actor);

        $this->assertNull($access->fresh()->revoked_at);
        $this->assertSame($expiry->format('Y-m-d H:i'), $access->fresh()->crm_access_expires_at->format('Y-m-d H:i'));
    }

    #[Test]
    public function extend_scope_updates_valid_to(): void
    {
        $access = ExternalAccess::factory()->create();
        $scope = $this->scope($access);
        $new = Carbon::now()->addMonths(2)->startOfSecond();

        $this->service()->extendScope($scope, $new, User::factory()->create());

        $this->assertSame($new->format('Y-m-d H:i:s'), $scope->fresh()->valid_to->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function end_scope_sets_valid_to_to_now(): void
    {
        $access = ExternalAccess::factory()->create();
        $scope = $this->scope($access);

        $this->service()->endScope($scope, User::factory()->create());

        $this->assertTrue($scope->fresh()->valid_to->lessThanOrEqualTo(now()->addSecond()));
    }

    #[Test]
    public function update_scope_access_type_changes_type(): void
    {
        $access = ExternalAccess::factory()->create();
        $scope = $this->scope($access);

        $this->service()->updateScopeAccessType($scope, ExternalAccessType::WRITE, User::factory()->create());

        $this->assertSame(ExternalAccessType::WRITE, $scope->fresh()->access_type);
    }

    #[Test]
    public function relink_supersedes_pending_submissions_and_changes_contact(): void
    {
        $actor = User::factory()->create();
        $access = ExternalAccess::factory()->create();
        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $access->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);

        $type = CrmContactType::query()->firstOrCreate(['slug' => 'relink-target'], ['name' => 'Target']);
        $newContact = CrmContact::create(['crm_contact_type_id' => $type->id, 'display_name' => 'New', 'is_active' => true]);

        $this->service()->relink($access, $newContact, $actor);

        $this->assertSame($newContact->id, $access->fresh()->crm_contact_id);
        $this->assertSame(ExternalSubmissionStatus::SUPERSEDED, $submission->fresh()->status);
    }

    #[Test]
    public function relink_to_same_contact_is_noop(): void
    {
        $actor = User::factory()->create();
        $access = ExternalAccess::factory()->create();
        $sameContact = $access->crmContact;

        $this->service()->relink($access, $sameContact, $actor);

        $this->assertSame($sameContact->id, $access->fresh()->crm_contact_id);
        $this->assertFalse(
            Activity::query()->where('description', 'access_relinked')->exists()
        );
    }
}
