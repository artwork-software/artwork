<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Modules\Calendar\DTO\CalendarShiftDTO;
use Artwork\Modules\Calendar\Services\CalendarShiftWorkerCounts;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Kalender-Kacheln bekommen ihre Zählstände per Aggregat-Abfrage (CalendarShiftWorkerCounts) statt
 * über die hydrierten Worker-Relationen. Das Ergebnis muss exakt der bisherigen Zählung über
 * Shift::users()/freelancer()/serviceProvider() entsprechen.
 */
final class CalendarShiftWorkerCountsTest extends FeatureTestCase
{
    private function assign(Shift $shift, string $type, int $id, int $qualificationId, bool $deleted = false): void
    {
        DB::table('shift_workers')->insert([
            'shift_id' => $shift->id,
            'employable_type' => $type,
            'employable_id' => $id,
            'shift_qualification_id' => $qualificationId,
            'craft_abbreviation' => 'X',
            'deleted_at' => $deleted ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalized(CalendarShiftDTO $dto): array
    {
        $assigned = $dto->assignedCounts ?? [];
        $global = $dto->globalAssignedCounts ?? [];
        ksort($assigned);
        ksort($global);

        return [$assigned, $global, $dto->assignedWorkersTotal];
    }

    #[Test]
    public function aggregated_counts_match_the_relation_based_counting(): void
    {
        $qualificationA = ShiftQualification::factory()->create();
        $qualificationB = ShiftQualification::factory()->create();
        $demanded = GlobalQualification::factory()->create();
        $notDemanded = GlobalQualification::factory()->create();

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $userA->globalQualifications()->attach([$demanded->id, $notDemanded->id]);
        $freelancer->globalQualifications()->attach($demanded->id);

        $shift = Shift::factory()->create();
        $shift->globalQualifications()->attach($demanded->id, ['quantity' => 2]);
        $shift->globalQualifications()->attach($notDemanded->id, ['quantity' => 0]);

        $this->assign($shift, User::class, $userA->id, $qualificationA->id);
        $this->assign($shift, User::class, $userB->id, $qualificationA->id);
        $this->assign($shift, Freelancer::class, $freelancer->id, $qualificationB->id);
        $this->assign($shift, ServiceProvider::class, $serviceProvider->id, $qualificationB->id);
        // gelöschte Zuweisung und verwaiste Person zählen nicht
        $this->assign($shift, User::class, User::factory()->create()->id, $qualificationB->id, deleted: true);
        $this->assign($shift, User::class, 999_999_999, $qualificationA->id);

        $emptyShift = Shift::factory()->create();

        $counts = app(CalendarShiftWorkerCounts::class)->forShiftIds([$shift->id, $emptyShift->id]);

        foreach ([$shift, $emptyShift] as $candidate) {
            $loaded = Shift::query()->whereKey($candidate->id)->with([
                'globalQualifications',
                'users:id,first_name,last_name',
                'users.globalQualifications:id',
                'freelancer:id,first_name,last_name',
                'freelancer.globalQualifications:id',
                'serviceProvider:id,provider_name',
                'serviceProvider.globalQualifications:id',
            ])->firstOrFail();
            $withoutRelations = Shift::query()->whereKey($candidate->id)->with('globalQualifications')->firstOrFail();

            $this->assertSame(
                $this->normalized(CalendarShiftDTO::fromModel($loaded)),
                $this->normalized(CalendarShiftDTO::fromModel(
                    $withoutRelations,
                    $counts[$candidate->id] ?? [[], [], 0]
                ))
            );
        }

        $dto = CalendarShiftDTO::fromModel(
            Shift::query()->whereKey($shift->id)->with('globalQualifications')->firstOrFail(),
            $counts[$shift->id]
        );
        $this->assertSame(4, $dto->assignedWorkersTotal);
        $this->assertSame([$qualificationA->id => 2, $qualificationB->id => 2], $this->normalized($dto)[0]);
        $this->assertSame([$demanded->id => 2], $dto->globalAssignedCounts);
    }
}
