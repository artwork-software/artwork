<?php

namespace Artwork\Modules\Shift\Seeders;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class ConsolidateShiftsSeeder
{
    public function seed(): void
    {
        if (ShiftWorker::count() > 0) {
            return;
        }

        DB::beginTransaction();
        try {
            $this->migrateShiftUsers();
            $this->migrateShiftFreelancers();
            $this->migrateShiftServiceProviders();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
    }

    private function migrateShiftUsers(): void
    {
        $shiftUsers = ShiftUser::withTrashed()->get();
        foreach ($shiftUsers as $shiftUser) {
            $exists = DB::table('shift_workers')
                ->where('shift_id', $shiftUser->shift_id)
                ->where('employable_type', User::class)
                ->where('employable_id', $shiftUser->user_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('shift_workers')->insert([
                'shift_id' => $shiftUser->shift_id,
                'employable_type' => User::class,
                'employable_id' => $shiftUser->user_id,
                'shift_qualification_id' => $shiftUser->shift_qualification_id,
                'shift_count' => $shiftUser->shift_count ?? 1,
                'craft_abbreviation' => $shiftUser->craft_abbreviation,
                'short_description' => $shiftUser->short_description,
                'start_date' => $shiftUser->start_date,
                'end_date' => $shiftUser->end_date,
                'start_time' => $shiftUser->start_time,
                'end_time' => $shiftUser->end_time,
                'created_at' => $shiftUser->created_at,
                'updated_at' => $shiftUser->updated_at,
                'deleted_at' => $shiftUser->deleted_at,
            ]);
        }
    }

    private function migrateShiftFreelancers(): void
    {
        $shiftFreelancers = ShiftFreelancer::withTrashed()->get();

        foreach ($shiftFreelancers as $shiftFreelancer) {
            $exists = DB::table('shift_workers')
                ->where('shift_id', $shiftFreelancer->shift_id)
                ->where('employable_type', Freelancer::class)
                ->where('employable_id', $shiftFreelancer->freelancer_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('shift_workers')->insert([
                'shift_id' => $shiftFreelancer->shift_id,
                'employable_type' => Freelancer::class,
                'employable_id' => $shiftFreelancer->freelancer_id,
                'shift_qualification_id' => $shiftFreelancer->shift_qualification_id,
                'shift_count' => $shiftFreelancer->shift_count ?? 1,
                'craft_abbreviation' => $shiftFreelancer->craft_abbreviation,
                'short_description' => $shiftFreelancer->short_description,
                'start_date' => $shiftFreelancer->start_date,
                'end_date' => $shiftFreelancer->end_date,
                'start_time' => $shiftFreelancer->start_time,
                'end_time' => $shiftFreelancer->end_time,
                'created_at' => $shiftFreelancer->created_at,
                'updated_at' => $shiftFreelancer->updated_at,
                'deleted_at' => $shiftFreelancer->deleted_at,
            ]);
        }
    }

    private function migrateShiftServiceProviders(): void
    {
        $shiftServiceProviders = ShiftServiceProvider::withTrashed()->get();

        foreach ($shiftServiceProviders as $shiftServiceProvider) {
            $exists = DB::table('shift_workers')
                ->where('shift_id', $shiftServiceProvider->shift_id)
                ->where('employable_type', ServiceProvider::class)
                ->where('employable_id', $shiftServiceProvider->service_provider_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('shift_workers')->insert([
                'shift_id' => $shiftServiceProvider->shift_id,
                'employable_type' => ServiceProvider::class,
                'employable_id' => $shiftServiceProvider->service_provider_id,
                'shift_qualification_id' => $shiftServiceProvider->shift_qualification_id,
                'shift_count' => $shiftServiceProvider->shift_count ?? 1,
                'craft_abbreviation' => $shiftServiceProvider->craft_abbreviation,
                'short_description' => $shiftServiceProvider->short_description,
                'start_date' => $shiftServiceProvider->start_date,
                'end_date' => $shiftServiceProvider->end_date,
                'start_time' => $shiftServiceProvider->start_time,
                'end_time' => $shiftServiceProvider->end_time,
                'created_at' => $shiftServiceProvider->created_at,
                'updated_at' => $shiftServiceProvider->updated_at,
                'deleted_at' => $shiftServiceProvider->deleted_at,
            ]);
        }
    }
}
