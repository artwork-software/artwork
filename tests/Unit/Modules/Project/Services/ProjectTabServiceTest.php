<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectTabService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabServiceTest extends TestCase
{
    private ProjectTabService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectTabService::class);
    }

    #[Test]
    public function find_first_project_tab_returns_a_tab(): void
    {
        $tab = ProjectTab::factory()->create(['order' => 1, 'visible_for_all' => true]);

        $result = $this->service->findFirstProjectTab();

        $this->assertNotNull($result);
        $this->assertInstanceOf(ProjectTab::class, $result);
    }

    #[Test]
    public function find_by_id_without_cache_returns_existing_tab(): void
    {
        $tab = ProjectTab::factory()->create();

        $found = $this->service->findByIdWithoutCache($tab->id);

        $this->assertNotNull($found);
        $this->assertSame($tab->id, $found->id);
    }

    #[Test]
    public function find_by_name_without_cache_returns_null_for_empty_string(): void
    {
        $this->assertNull($this->service->findByNameWithoutCache(''));
    }

    #[Test]
    public function find_by_name_without_cache_returns_existing_tab(): void
    {
        $tab = ProjectTab::factory()->create(['name' => 'My Tab Name']);

        $found = $this->service->findByNameWithoutCache('My Tab Name');

        $this->assertNotNull($found);
        $this->assertSame($tab->id, $found->id);
    }

    #[Test]
    public function get_default_or_first_project_tab_id_returns_int(): void
    {
        ProjectTab::factory()->create(['default' => true, 'visible_for_all' => true]);

        $id = $this->service->getDefaultOrFirstProjectTabId();

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }
}
