<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion "eigene Arbeitszeit-Änderungsanfrage zurückziehen".
 */
final class WorkTimeChangeRequestDestroyTest extends FeatureTestCase
{
    #[Test]
    public function own_pending_request_is_deleted(): void
    {
        $worker = User::factory()->create();
        $request = $this->createRequest($worker);
        $otherRequest = $this->createRequest($worker);

        $this->actingAs($worker)
            ->delete(route('worktime.change-request.destroy', $request))
            ->assertRedirect();

        $this->assertNull(WorkTimeChangeRequest::query()->find($request->id));
        $this->assertNotNull(WorkTimeChangeRequest::query()->find($otherRequest->id));
    }

    #[Test]
    public function decided_requests_cannot_be_deleted(): void
    {
        $worker = User::factory()->create();
        $approved = $this->createRequest($worker, 'approved');
        $rejected = $this->createRequest($worker, 'rejected');

        $this->actingAs($worker)->delete(route('worktime.change-request.destroy', $approved))->assertForbidden();
        $this->actingAs($worker)->delete(route('worktime.change-request.destroy', $rejected))->assertForbidden();

        $this->assertNotNull(WorkTimeChangeRequest::query()->find($approved->id));
        $this->assertNotNull(WorkTimeChangeRequest::query()->find($rejected->id));
    }

    #[Test]
    public function request_of_another_person_cannot_be_deleted_even_by_admin(): void
    {
        $request = $this->createRequest(User::factory()->create());

        $this->actingAsAdmin();
        $this->delete(route('worktime.change-request.destroy', $request))->assertForbidden();

        $this->assertNotNull(WorkTimeChangeRequest::query()->find($request->id));
    }

    #[Test]
    public function unknown_request_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('worktime.change-request.destroy', 99999999))
            ->assertNotFound();
    }

    private function createRequest(User $worker, string $status = 'pending'): WorkTimeChangeRequest
    {
        return WorkTimeChangeRequest::query()->create([
            'user_id' => $worker->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'craft_id' => Craft::factory()->create()->id,
            'status' => $status,
            'requested_by' => $worker->id,
        ]);
    }
}
