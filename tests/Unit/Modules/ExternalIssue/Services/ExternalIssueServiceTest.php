<?php

namespace Tests\Unit\Modules\ExternalIssue\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\ExternalIssue\Services\ExternalIssueService;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalIssueServiceTest extends TestCase
{
    private ExternalIssueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        // Authenticate a user so activity()->causedBy() does not bomb on null
        $this->actingAs(User::factory()->create());
        $this->service = app(ExternalIssueService::class);
    }

    #[Test]
    public function store_creates_external_issue(): void
    {
        $data = [
            'name' => 'My issue',
            'external_name' => 'External Guy',
            'external_address' => 'Some Street 1',
            'external_email' => 'guy@example.test',
            'external_phone' => '+49 30 1234',
            'issue_date' => '2025-01-01',
            'return_date' => '2025-02-01',
            'material_value' => 123.45,
        ];

        $issue = $this->service->store($data);

        $this->assertInstanceOf(ExternalIssue::class, $issue);
        $this->assertTrue($issue->exists);
        $this->assertDatabaseHas('external_issues', ['name' => 'My issue']);
    }

    #[Test]
    public function delete_removes_external_issue_and_files(): void
    {
        $issue = ExternalIssue::factory()->create();
        ExternalIssueFile::create([
            'external_issue_id' => $issue->id,
            'file_path' => 'external/dummy.txt',
            'original_name' => 'dummy.txt',
        ]);

        $this->service->delete($issue->fresh(['files', 'articles', 'specialItems']));

        $this->assertDatabaseMissing('external_issues', ['id' => $issue->id]);
        $this->assertDatabaseMissing('external_issue_files', ['external_issue_id' => $issue->id]);
    }
}
