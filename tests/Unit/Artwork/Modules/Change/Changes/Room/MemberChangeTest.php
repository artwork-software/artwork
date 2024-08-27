<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\MemberChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class MemberChangeTest extends TestCase
{
    public function testMemberChange(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create();
        $oldAdmin1 = User::factory()->create();
        $oldAdmin2 = User::factory()->create();
        $oldRoom->admins()->attach([$oldAdmin1->id, $oldAdmin2->id]);

        $newRoom = Room::factory()->create();
        $newAdmin1 = User::factory()->create();
        $newAdmin2 = User::factory()->create();
        $newAdmin3 = User::factory()->create();
        $newRoom->admins()->attach([$newAdmin1->id, $newAdmin2->id, $newAdmin3->id]);

        $changeService = new ChangeService(new ChangeRepository());
        $memberChange = new MemberChange(app()->get(NotificationService::class), $changeService);

        $memberChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseMissing($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testNoMemberChange(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create();
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();
        $oldRoom->admins()->attach([$admin1->id, $admin2->id]);

        $newRoom = Room::factory()->create();
        $newRoom->admins()->attach([$admin1->id, $admin2->id]);

        $changeService = new ChangeService(new ChangeRepository());
        $notificationService = app()->get(NotificationService::class);
        $memberChange = new MemberChange($notificationService, $changeService);

        $memberChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseMissing($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
