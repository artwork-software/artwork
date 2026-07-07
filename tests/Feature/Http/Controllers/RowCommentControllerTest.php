<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RowCommentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_row_comment(): void
    {
        $row = SubPositionRow::factory()->create();

        $this->post(route('project.budget.row.comment.store', $row), [
            'description' => 'Hi',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_row_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $row = SubPositionRow::factory()->create();

        $response = $this->post(route('project.budget.row.comment.store', $row), [
            'description' => 'My comment',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('row_comments', [
            'sub_position_row_id' => $row->id,
            'user_id' => $admin->id,
            'description' => 'My comment',
        ]);
    }

    #[Test]
    public function guest_cannot_destroy_row_comment(): void
    {
        // SERVICE-BUG: RowCommentService::delete() does not exist (RowCommentController.php:33).
        // Only test that route is auth-protected; destroy happy-path is impossible until service is fixed.
        $comment = RowComment::factory()->create();

        $this->delete(route('project.budget.row.comment.delete', $comment))
            ->assertRedirect(route('login'));
    }
}
