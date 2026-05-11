<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Services\SumCommentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SumCommentServiceTest extends TestCase
{
    private SumCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SumCommentService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_comment(): void
    {
        $comment = SumComment::factory()->create();

        $this->service->softDelete($comment);

        $this->assertSoftDeleted('sum_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function force_delete_removes_comment(): void
    {
        $comment = SumComment::factory()->create();

        $this->service->forceDelete($comment);

        $this->assertDatabaseMissing('sum_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_comment(): void
    {
        $comment = SumComment::factory()->create();
        $comment->delete();

        $this->service->restore($comment);

        $this->assertDatabaseHas('sum_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }
}
