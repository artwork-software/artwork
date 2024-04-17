<?php

namespace Tests\Unit\App\Support\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Models\User;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Shift\Models\Shift;
use Tests\TestCase;

class ChangeServiceTest extends TestCase
{
    protected User $user;
    public function setUp(): void
    {
        parent::setUp();
        $this->user = $this->adminUser();
        $this->actingAs($this->user);
    }

    public function testSaveFromBuilder(): void
    {
        $shift = Shift::factory()->create();

        $service = new ChangeService(new ChangeRepository());

        $service->saveFromBuilder(
            $service->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('test.translation.key')
                ->setTranslationKeyPlaceholderValues(['key' => 'value'])
        );

        $change = Change::query()
            ->where('model_id', $shift->id)
            ->where('model_type', Shift::class)
            ->where('changer_id', $this->user->id)
            ->first();

        $this->assertNotNull($change);
    }
}
