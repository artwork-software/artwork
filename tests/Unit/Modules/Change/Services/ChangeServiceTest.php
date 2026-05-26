<?php

namespace Tests\Unit\Modules\Change\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
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

        $change = $this->service->saveFromBuilder($builder);

        $this->assertInstanceOf(Change::class, $change);
        $this->assertTrue($change->exists);
        $this->assertSame(User::class, $change->model_type);
        $this->assertSame($user->id, $change->model_id);
    }

    #[Test]
    public function save_from_builder_throws_when_required_fields_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = $this->service->createBuilder();
        $this->service->saveFromBuilder($builder);
    }
}
