<?php

namespace Tests\Unit\Modules\Change\Services;

use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    #[Test]
    public function the_ai_origin_is_hidden_from_viewers_without_ai_access(): void
    {
        $project = $this->projectWithAiActivity();

        Auth::login(User::factory()->create());
        $withoutAccess = $this->service->historyForFrontend($project->fresh());

        $this->assertNull($withoutAccess[0]['via']);
        $this->assertNull($withoutAccess[0]['via_detail']);

        Auth::login($this->adminUser());
        $this->assertNull($this->service->historyForFrontend($project->fresh())[0]['via']);
    }

    #[Test]
    public function the_ai_origin_is_shown_to_viewers_with_ai_access(): void
    {
        $project = $this->projectWithAiActivity();

        Auth::login(User::factory()->withAiAccess()->create());
        $history = $this->service->historyForFrontend($project->fresh());

        $this->assertSame(AttachActivityOrigin::VIA_AI_ASSISTANT, $history[0]['via']);
        $this->assertSame('conversation-1', $history[0]['via_detail']);
    }

    private function projectWithAiActivity(): Project
    {
        $project = Project::factory()->create();
        $project->activities()->delete();

        $activity = activity()->performedOn($project)->log('probe');

        // Direkt in die Tabelle: der Origin-Listener würde `via` beim Speichern neu setzen.
        DB::table($activity->getTable())->where('id', $activity->id)->update([
            'properties' => json_encode([
                ['type' => 'string', 'title' => 'Name geändert'],
                'via' => AttachActivityOrigin::VIA_AI_ASSISTANT,
                'via_detail' => 'conversation-1',
            ]),
        ]);

        return $project;
    }
}
