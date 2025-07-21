<?php

namespace Tests\Feature\User;

use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_be_deleted_without_foreign_key_errors(): void
    {
        // Create a test user
        $user = User::factory()->create();

        // Create some related records for the user
        // This will ensure the user has relationships that need to be handled
        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();

        // Create an inventory article plan filter for the user
        // This specifically tests the relationship mentioned in the issue description
        $user->inventoryArticlePlanFilter()->create([
            'start_date' => now(),
            'end_date' => now()->addDays(7)
        ]);

        // Get the admin user to perform the deletion
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'artwork-admin');
        })->first();

        if (!$admin) {
            $this->markTestSkipped('No admin user found to perform the test');
        }

        // Act as the admin user
        $this->actingAs($admin);

        // Attempt to delete the user
        $response = $this->delete(route('users.destroy', $user));

        // Assert that the deletion was successful
        $response->assertRedirect(route('users'));

        // Assert that the user no longer exists in the database
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
