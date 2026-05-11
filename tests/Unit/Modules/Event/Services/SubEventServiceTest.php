<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\Event\Services\SubEventService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SubEventServiceTest extends TestCase
{
    private SubEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubEventService::class);
    }

    #[Test]
    public function delete_soft_deletes_sub_event(): void
    {
        $sub = SubEvent::factory()->create();

        $this->service->delete($sub);

        $this->assertSoftDeleted('sub_events', ['id' => $sub->id]);
    }

    #[Test]
    public function delete_sub_events_handles_collection(): void
    {
        $subs = SubEvent::factory()->count(2)->create();

        $this->service->deleteSubEvents($subs);

        foreach ($subs as $sub) {
            $this->assertSoftDeleted('sub_events', ['id' => $sub->id]);
        }
    }

    #[Test]
    public function restore_sub_events_restores_collection(): void
    {
        $subs = SubEvent::factory()->count(2)->create();
        foreach ($subs as $sub) {
            $sub->delete();
        }

        $this->service->restoreSubEvents(SubEvent::withTrashed()->whereIn('id', $subs->pluck('id'))->get());

        foreach ($subs as $sub) {
            $this->assertNotSoftDeleted('sub_events', ['id' => $sub->id]);
        }
    }

    #[Test]
    public function restore_restores_single_sub_event(): void
    {
        $sub = SubEvent::factory()->create();
        $sub->delete();

        $this->service->restore(SubEvent::withTrashed()->find($sub->id));

        $this->assertNotSoftDeleted('sub_events', ['id' => $sub->id]);
    }

    #[Test]
    public function force_delete_removes_sub_event(): void
    {
        $sub = SubEvent::factory()->create();

        $this->service->forceDelete($sub);

        $this->assertDatabaseMissing('sub_events', ['id' => $sub->id]);
    }

    #[Test]
    public function force_delete_sub_events_removes_collection(): void
    {
        $subs = SubEvent::factory()->count(2)->create();

        $this->service->forceDeleteSubEvents($subs);

        $this->assertSame(0, SubEvent::withTrashed()->whereIn('id', $subs->pluck('id'))->count());
    }
}
