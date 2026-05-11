<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Services\CellCommentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CellCommentServiceTest extends TestCase
{
    private CellCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CellCommentService::class);
    }

    #[Test]
    public function delete_soft_deletes_comment(): void
    {
        $comment = CellComment::factory()->create();

        $this->service->delete($comment);

        $this->assertSoftDeleted('cell_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function force_delete_removes_comment(): void
    {
        $comment = CellComment::factory()->create();

        $this->service->forceDelete($comment);

        $this->assertDatabaseMissing('cell_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_comment(): void
    {
        $comment = CellComment::factory()->create();
        $comment->delete();

        $this->service->restore($comment);

        $this->assertDatabaseHas('cell_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }
}
