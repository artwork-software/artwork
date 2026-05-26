<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CellCommentsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_cell_comment(): void
    {
        $cell = ColumnCell::factory()->create();

        $this->post(route('project.budget.cell.comment.store', $cell), [
            'description' => 'Hi',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_cell_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $cell = ColumnCell::factory()->create();

        $response = $this->post(route('project.budget.cell.comment.store', $cell), [
            'description' => 'My comment',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cell_comments', [
            'column_cell_id' => $cell->id,
            'user_id' => $admin->id,
            'description' => 'My comment',
        ]);
    }

    #[Test]
    public function admin_can_destroy_cell_comment(): void
    {
        $this->actingAsAdmin();
        $comment = CellComment::factory()->create();

        $response = $this->delete(route('project.budget.cell.comment.delete', $comment));

        $response->assertRedirect();
        $this->assertSoftDeleted('cell_comments', ['id' => $comment->id]);
    }
}
