<?php

namespace Tests\Unit\Modules\Checklist\Services;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Checklist\Services\ChecklistTemplateService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChecklistTemplateServiceTest extends TestCase
{
    private ChecklistTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChecklistTemplateService::class);
    }

    #[Test]
    public function create_checklist_template_returns_unsaved_model(): void
    {
        $template = $this->service->createChecklistTemplate(['name' => 'A template']);

        $this->assertInstanceOf(ChecklistTemplate::class, $template);
        $this->assertFalse($template->exists);
        $this->assertSame('A template', $template->name);
    }

    #[Test]
    public function duplicate_persists_copy_with_suffix(): void
    {
        $owner = User::factory()->create();
        $template = ChecklistTemplate::factory()->create(['name' => 'Source', 'user_id' => $owner->id]);
        $template->load('users');

        $newOwner = User::factory()->create();

        $copy = $this->service->duplicate($template, $newOwner->id);

        $this->assertTrue($copy->exists);
        $this->assertSame('Source (Kopie)', $copy->name);
        $this->assertSame($newOwner->id, $copy->user_id);
    }
}
