<?php

namespace Tests\Unit\Modules\Change\Services;

use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Contracts\Activity;
use Tests\TestCase;

final class ChangeServiceTest extends TestCase
{
    private ChangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChangeService::class);
    }

    #[Test]
    public function create_builder_returns_change_builder_instance(): void
    {
        $builder = $this->service->createBuilder();

        $this->assertInstanceOf(ChangeBuilder::class, $builder);
    }

    #[Test]
    public function save_from_builder_persists_change(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $builder = $this->service->createBuilder()
            ->setType('project')
            ->setModelClass(User::class)
            ->setModelId($user->id)
            ->setTranslationKey('something.changed');

        $activity = $this->service->saveFromBuilder($builder);

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertTrue($activity->exists);
        $this->assertSame(User::class, $activity->subject_type);
        $this->assertSame($user->id, $activity->subject_id);
    }

    #[Test]
    public function save_from_builder_throws_when_required_fields_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = $this->service->createBuilder();
        $this->service->saveFromBuilder($builder);
    }
}
