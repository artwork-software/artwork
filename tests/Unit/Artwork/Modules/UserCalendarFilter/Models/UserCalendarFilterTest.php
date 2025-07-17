<?php

namespace Tests\Unit\Artwork\Modules\UserCalendarFilter\Models;

use Artwork\Modules\User\Models\UserCalendarFilter;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserCalendarFilterTest extends TestCase
{
    public function testCasts(): void
    {
        $filter = UserCalendarFilter::create([
            'is_loud' => '1',
            'is_not_loud' => '0',
            'user_id' => $this->adminUser()->id,
            'adjoining_not_loud' => '1',
            'has_audience' => '0',
            'has_no_audience' => '1',
            'adjoining_no_audience' => '0',
            'show_free_rooms' => '1',
            'show_adjoining_rooms' => '0',
            'all_day_free' => '1',
        ]);

        $this->assertTrue($filter->is_loud);
        $this->assertFalse($filter->is_not_loud);
        $this->assertTrue($filter->adjoining_not_loud);
        $this->assertFalse($filter->has_audience);
        $this->assertTrue($filter->has_no_audience);
        $this->assertFalse($filter->adjoining_no_audience);
        $this->assertTrue($filter->show_free_rooms);
        $this->assertFalse($filter->show_adjoining_rooms);
        $this->assertTrue($filter->all_day_free);
    }
}
