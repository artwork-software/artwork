<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\EventComment;
use Artwork\Modules\Event\Services\EventCommentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventCommentServiceTest extends TestCase
{
    private EventCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventCommentService::class);
    }

    #[Test]
    public function delete_soft_deletes_a_single_comment(): void
    {
        $comment = EventComment::factory()->create();

        $this->service->delete($comment);

        $this->assertSoftDeleted('event_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function delete_event_comments_soft_deletes_collection(): void
    {
        $comments = EventComment::factory()->count(3)->create();

        $this->service->deleteEventComments($comments);

        foreach ($comments as $comment) {
            $this->assertSoftDeleted('event_comments', ['id' => $comment->id]);
        }
    }

    #[Test]
    public function restore_event_comments_restores_collection(): void
    {
        $comments = EventComment::factory()->count(2)->create();
        foreach ($comments as $comment) {
            $comment->delete();
        }

        $this->service->restoreEventComments(EventComment::withTrashed()->whereIn('id', $comments->pluck('id'))->get());

        foreach ($comments as $comment) {
            $this->assertNotSoftDeleted('event_comments', ['id' => $comment->id]);
        }
    }

    #[Test]
    public function force_delete_removes_comment_permanently(): void
    {
        $comment = EventComment::factory()->create();

        $this->service->forceDelete($comment);

        $this->assertDatabaseMissing('event_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_comment(): void
    {
        $comment = EventComment::factory()->create();
        $comment->delete();

        $this->service->restore(EventComment::withTrashed()->find($comment->id));

        $this->assertNotSoftDeleted('event_comments', ['id' => $comment->id]);
    }
}
