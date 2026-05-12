<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Artwork\Modules\WorkTime\Services\WorkTimeChangeRequestService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkTimeChangeRequestServiceTest extends TestCase
{
    private WorkTimeChangeRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkTimeChangeRequestService::class);
    }

    #[Test]
    public function create_change_request_persists_with_pending_status(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        $craft = Craft::factory()->create();

        $data = [
            'user_id' => $user->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'request_end_date' => '2025-05-01',
            'shift_id' => null,
            'craft_id' => $craft->id,
            'request_comment' => 'I want to change my time.',
            'requested_by' => $requester->id,
        ];

        $request = $this->service->createChangeRequest($data);

        $this->assertInstanceOf(WorkTimeChangeRequest::class, $request);
        $this->assertSame('pending', $request->status);
        $this->assertDatabaseHas('work_time_change_requests', [
            'id' => $request->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
