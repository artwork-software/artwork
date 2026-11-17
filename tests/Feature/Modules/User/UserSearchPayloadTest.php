<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Personensuche (users.search): Treffer ohne Schichtliste. Vorher lud jede Suche alle Schichten der
 * bis zu 25 Treffer und je Schicht Termin, Raum und Gewerk nach (N+1, Typeahead an vielen Stellen).
 */
final class UserSearchPayloadTest extends FeatureTestCase
{
    private function createMatchesWithShifts(int $shiftsPerUser): void
    {
        $qualification = ShiftQualification::factory()->create();
        foreach (range(1, 3) as $index) {
            $user = User::factory()->create(['first_name' => 'Zxqsuche' . $index, 'last_name' => 'Test']);
            foreach (range(1, $shiftsPerUser) as $ignored) {
                Shift::factory()->create()->users()->attach($user->id, [
                    'shift_qualification_id' => $qualification->id,
                ]);
            }
        }
    }

    private function countSearchQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('users.search', ['query' => 'Zxqsuche']))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    #[Test]
    public function search_results_keep_their_shape_without_shift_list(): void
    {
        $this->createMatchesWithShifts(2);
        $this->actingAsAdmin();

        $response = $this->getJson(route('users.search', ['query' => 'Zxqsuche']))->assertOk();

        self::assertCount(3, $response->json());
        foreach ($response->json() as $result) {
            self::assertSame([], $result['shifts']);
            self::assertArrayHasKey('departments', $result);
            self::assertSame('UserIndexResource', $result['resource']);
        }
    }

    #[Test]
    public function search_query_count_does_not_grow_with_shifts(): void
    {
        $this->actingAsAdmin();
        $this->createMatchesWithShifts(1);
        $withOneShift = $this->countSearchQueries();

        $this->createMatchesWithShifts(4);
        $withMoreShifts = $this->countSearchQueries();

        // doppelt so viele Treffer (6 statt 3), viermal so viele Schichten: höchstens konstanter Mehraufwand
        self::assertLessThanOrEqual($withOneShift + 2, $withMoreShifts);
    }
}
