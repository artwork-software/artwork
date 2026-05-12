<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Services\UserWorkTimePatternService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserWorkTimePatternServiceTest extends TestCase
{
    private UserWorkTimePatternService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserWorkTimePatternService::class);
    }

    #[Test]
    public function create_persists_pattern(): void
    {
        $pattern = $this->service->create([
            'name' => 'Vollzeit',
            'monday' => '08:00:00',
            'tuesday' => '08:00:00',
            'wednesday' => '08:00:00',
            'thursday' => '08:00:00',
            'friday' => '08:00:00',
            'saturday' => '00:00:00',
            'sunday' => '00:00:00',
        ]);

        $this->assertInstanceOf(UserWorkTimePattern::class, $pattern);
        $this->assertTrue($pattern->exists);
        $this->assertDatabaseHas('user_work_time_patterns', ['name' => 'Vollzeit']);
    }

    #[Test]
    public function update_modifies_pattern(): void
    {
        $pattern = UserWorkTimePattern::factory()->create(['name' => 'Old']);

        $updated = $this->service->update($pattern, [
            'name' => 'New',
            'monday' => '09:00:00',
            'tuesday' => '09:00:00',
            'wednesday' => '09:00:00',
            'thursday' => '09:00:00',
            'friday' => '09:00:00',
            'saturday' => '00:00:00',
            'sunday' => '00:00:00',
        ]);

        $this->assertSame('New', $updated->name);
        $this->assertDatabaseHas('user_work_time_patterns', ['id' => $pattern->id, 'name' => 'New']);
    }
}
