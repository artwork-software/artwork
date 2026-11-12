<?php

namespace Tests\Feature\Modules\Contract;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractFollowUpTasksTest extends FeatureTestCase
{
    #[Test]
    public function every_new_follow_up_task_gets_its_own_assigned_users(): void
    {
        $creator = $this->actingAsAdmin();
        $contract = Contract::factory()->create(['creator_id' => $creator->id]);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->patch(route('contracts.update', $contract), [
            'tasks' => [
                [
                    'new' => true,
                    'name' => 'Erste Aufgabe',
                    'description' => '',
                    'deadline' => '2027-01-10',
                    'assigned_users' => [['id' => $firstUser->id]],
                ],
                [
                    'new' => true,
                    'name' => 'Zweite Aufgabe',
                    'description' => '',
                    'deadline' => '2027-01-11',
                    'assigned_users' => [['id' => $secondUser->id]],
                ],
                [
                    'new' => true,
                    'name' => 'Dritte Aufgabe',
                    'description' => '',
                    'deadline' => '2027-01-12',
                ],
            ],
        ])->assertRedirect();

        $tasks = Task::query()->where('contract_id', $contract->id)->get()->keyBy('name');

        $this->assertCount(3, $tasks);
        $this->assertSame([$firstUser->id], $tasks['Erste Aufgabe']->task_users()->pluck('users.id')->all());
        $this->assertSame([$secondUser->id], $tasks['Zweite Aufgabe']->task_users()->pluck('users.id')->all());
        $this->assertSame([], $tasks['Dritte Aufgabe']->task_users()->pluck('users.id')->all());
    }
}
