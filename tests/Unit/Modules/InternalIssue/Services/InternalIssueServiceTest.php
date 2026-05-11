<?php

namespace Tests\Unit\Modules\InternalIssue\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\InternalIssue\Services\InternalIssueService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InternalIssueServiceTest extends TestCase
{
    private InternalIssueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->service = app(InternalIssueService::class);
    }

    #[Test]
    public function store_creates_internal_issue(): void
    {
        $data = [
            'name' => 'Internal issue test',
            'start_date' => '2025-01-01',
            'start_time' => '08:00',
            'end_date' => '2025-01-02',
            'end_time' => '16:00',
            'notes' => 'Some notes',
            'special_items_done' => false,
        ];

        $issue = $this->service->store($data);

        $this->assertInstanceOf(InternalIssue::class, $issue);
        $this->assertTrue($issue->exists);
        $this->assertDatabaseHas('internal_issues', ['name' => 'Internal issue test']);
    }

    #[Test]
    public function delete_removes_internal_issue_and_files(): void
    {
        $issue = InternalIssue::factory()->create();
        InternalIssueFile::create([
            'internal_issue_id' => $issue->id,
            'file_path' => 'internal/dummy.txt',
            'original_name' => 'dummy.txt',
        ]);

        $this->service->delete($issue->fresh(['files', 'articles', 'specialItems', 'project']));

        $this->assertDatabaseMissing('internal_issues', ['id' => $issue->id]);
        $this->assertDatabaseMissing('internal_issue_files', ['internal_issue_id' => $issue->id]);
    }
}
