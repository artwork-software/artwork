<?php

namespace Database\Seeders;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftQualificationIconsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShiftQualification::where('icon', 'user-icon')->update(['icon' => 'IconUser']);
        ShiftQualification::where('icon', 'academic-cap-icon')->update(['icon' => 'IconBrandRedhat']);
        ShiftQualification::where('icon', 'bell-icon')->update(['icon' => 'IconBell']);
        ShiftQualification::where('icon', 'chat-icon')->update(['icon' => 'IconMessageDots']);
        ShiftQualification::where('icon', 'adjustments-icon')->update(['icon' => 'IconAdjustmentsAlt']);
        ShiftQualification::where('icon', 'book-open-icon')->update(['icon' => 'IconBook']);
        ShiftQualification::where('icon', 'briefcase-icon')->update(['icon' => 'IconBriefcase']);
        ShiftQualification::where('icon', 'camera-icon')->update(['icon' => 'IconCamera']);
        ShiftQualification::where('icon', 'clipboard-icon')->update(['icon' => 'IconClipboard']);
        ShiftQualification::where('icon', 'eye-icon')->update(['icon' => 'IconEye']);
        ShiftQualification::where('icon', 'film-icon')->update(['icon' => 'IconMovie']);
    }
}
