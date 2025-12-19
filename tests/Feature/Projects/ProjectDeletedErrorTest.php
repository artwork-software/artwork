<?php

namespace Tests\Feature\Projects;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectDeletedErrorTest extends TestCase
{
    public function testProjectTabRouteShowsFriendlyMessageWhenProjectDoesNotExist(): void
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->adminUser());

        $response = $this->get('/projects/999999999/tab/1', [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/ProjectDeleted')
                ->where('message', 'This project has already been deleted.')
            );
    }
}
