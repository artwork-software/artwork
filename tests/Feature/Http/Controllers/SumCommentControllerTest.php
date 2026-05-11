<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SumComment;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SumCommentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_sum_comment(): void
    {
        $this->post(route('sum.comments.store'), [
            'comment' => 'Hi',
            'commentable_id' => 1,
            'commentable_type' => BudgetSumDetails::class,
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_sum_comment(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post(route('sum.comments.store'), [
            'comment' => 'A note',
            'commentable_id' => 1,
            'commentable_type' => BudgetSumDetails::class,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sum_comments', [
            'comment' => 'A note',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function admin_can_destroy_sum_comment(): void
    {
        $this->actingAsAdmin();
        $comment = SumComment::factory()->create();

        $response = $this->delete(route('sum.comments.delete', $comment));

        $response->assertRedirect();
        $this->assertSoftDeleted('sum_comments', ['id' => $comment->id]);
    }
}
