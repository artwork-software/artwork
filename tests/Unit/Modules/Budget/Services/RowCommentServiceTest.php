<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Services\RowCommentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RowCommentServiceTest extends TestCase
{
    private RowCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RowCommentService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_comment(): void
    {
        $comment = RowComment::factory()->create();

        $this->service->softDelete($comment);

        $this->assertSoftDeleted('row_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function force_delete_removes_comment(): void
    {
        $comment = RowComment::factory()->create();

        $this->service->forceDelete($comment);

        $this->assertDatabaseMissing('row_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_comment(): void
    {
        $comment = RowComment::factory()->create();
        $comment->delete();

        $this->service->restore($comment);

        $this->assertDatabaseHas('row_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }
}
