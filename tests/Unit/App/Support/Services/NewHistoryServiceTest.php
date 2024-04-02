<?php

namespace Tests\Unit\App\Support\Services;

use App\Models\User;
use App\Support\Services\NewHistoryService;
use Artwork\Modules\Shift\Models\Shift;
use Antonrom\ModelChangesHistory\Models\Change;
use Tests\TestCase;

class NewHistoryServiceTest extends TestCase
{
    protected User $user;
    public function setUp(): void
    {
        parent::setUp();
        $this->user = $this->adminUser();
        $this->actingAs($this->user);
    }

    public function testCreate(): void
    {
        $shift = Shift::factory()->create();

        $service = new NewHistoryService(Shift::class);
        $service->setModelId($shift->id);
        $service->setTranslationKey('test.translation.key');
        $service->setTranslationKeyPlaceholderValues(['key' => 'value']);
        $service->setType('shift');

        $service->create();

        $change = Change::query()
            ->where('model_id', $shift->id)
            ->where('model_type', Shift::class)
            ->where('changer_id', $this->user->id)
            ->first();

        $this->assertNotNull($change);
    }

    public function testCreateHistory(): void
    {
        $shift = Shift::factory()->create();

        $service = new NewHistoryService(Shift::class);
        $service->createHistory(
            $shift->id,
            'test.translation.key',
            ['key' => 'value'],
            'shift'
        );

        $change = Change::query()
            ->where('model_id', $shift->id)
            ->where('model_type', Shift::class)
            ->where('changer_id', $this->user->id)
            ->first();

        $this->assertNotNull($change);
    }
}
