<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationEnum;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion von Freelancer-Abwesenheit anlegen sowie Abwesenheit bearbeiten/löschen über die
 * Legacy-Routen (update/destroy). Rechte deckt die Rollen-Matrix ab.
 */
final class VacationWorkerRoutesTest extends FeatureTestCase
{
    private Freelancer $freelancer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->freelancer = Freelancer::factory()->create();
    }

    #[Test]
    public function freelancer_vacation_single_day_is_created_as_not_available(): void
    {
        $this->post(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'full_day' => true,
            'comment' => 'Urlaub',
        ])->assertSuccessful();

        $vacations = $this->freelancerVacations();
        $this->assertCount(1, $vacations);
        $this->assertSame('2026-12-01', $this->dateOf($vacations->first()));
        $this->assertSame(VacationEnum::NOT_AVAILABLE->value, $this->typeValue($vacations->first()));
        $this->assertSame('Urlaub', $vacations->first()->comment);
    }

    #[Test]
    public function freelancer_vacation_period_creates_daily_series(): void
    {
        $this->post(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'full_day' => true,
            'is_series' => true,
            'series_repeat' => 'daily',
            'series_repeat_until' => '2026-12-04',
        ])->assertSuccessful();

        $vacations = $this->freelancerVacations();
        $this->assertSame(
            ['2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04'],
            $vacations->map(fn (Vacation $vacation) => $this->dateOf($vacation))->all()
        );
        $this->assertCount(1, $vacations->pluck('series_id')->unique());
        $this->assertNotNull($vacations->first()->series_id);
    }

    #[Test]
    public function freelancer_availability_is_created_for_type_available(): void
    {
        $this->post(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'available',
            'full_day' => true,
        ])->assertSuccessful();

        $this->assertCount(0, $this->freelancerVacations());
        $this->assertTrue(
            Availability::query()
                ->where('available_type', Freelancer::class)
                ->where('available_id', $this->freelancer->id)
                ->whereDate('date', '2026-12-01')
                ->exists()
        );
    }

    #[Test]
    public function freelancer_vacation_series_without_frequency_is_rejected_instead_of_server_error(): void
    {
        $this->postJson(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'is_series' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['series_repeat', 'series_repeat_until']);

        $this->postJson(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'is_series' => true,
            'series_repeat' => 'monthly',
            'series_repeat_until' => '2026-11-01',
        ])->assertStatus(422)->assertJsonValidationErrors(['series_repeat', 'series_repeat_until']);

        $this->assertCount(0, $this->freelancerVacations());
    }

    #[Test]
    public function freelancer_vacation_with_invalid_date_or_time_is_rejected_instead_of_server_error(): void
    {
        $this->postJson(route('freelancer.vacation.add', $this->freelancer), [
            'date' => 'kein-datum',
            'type' => 'vacation',
        ])->assertStatus(422)->assertJsonValidationErrors('date');

        $this->postJson(route('freelancer.vacation.add', $this->freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'full_day' => false,
            'start_time' => 'morgens',
            'end_time' => '25:99',
        ])->assertStatus(422)->assertJsonValidationErrors(['start_time', 'end_time']);

        $this->assertCount(0, $this->freelancerVacations());
    }

    #[Test]
    public function freelancer_vacation_update_moves_date_and_comment(): void
    {
        $vacation = $this->createFreelancerVacation();

        $this->patch(route('freelancer.vacation.update', $vacation), [
            'id' => $vacation->id,
            'date' => '2026-12-05',
            'full_day' => true,
            'comment' => 'verschoben',
            'type' => 'vacation',
            'type_before_update' => 'vacation',
        ])->assertRedirect();

        $fresh = $vacation->fresh();
        $this->assertSame('2026-12-05', $this->dateOf($fresh));
        $this->assertSame('verschoben', $fresh->comment);
        $this->assertSame(VacationEnum::NOT_AVAILABLE->value, $this->typeValue($fresh));
    }

    #[Test]
    public function update_vacation_route_changes_type_to_availability(): void
    {
        $vacation = $this->createFreelancerVacation();

        $this->patch(route('update.vacation', $vacation), [
            'id' => $vacation->id,
            'date' => '2026-12-01',
            'full_day' => true,
            'type' => 'available',
            'type_before_update' => 'vacation',
        ])->assertRedirect();

        $this->assertNull($vacation->fresh());
        $this->assertTrue(
            Availability::query()
                ->where('available_type', Freelancer::class)
                ->where('available_id', $this->freelancer->id)
                ->whereDate('date', '2026-12-01')
                ->exists()
        );
    }

    #[Test]
    public function update_vacation_rejects_invalid_date_instead_of_server_error(): void
    {
        $vacation = $this->createFreelancerVacation();

        $this->patchJson(route('update.vacation', $vacation), [
            'id' => $vacation->id,
            'date' => 'irgendwann',
            'type' => 'vacation',
            'type_before_update' => 'vacation',
        ])->assertStatus(422)->assertJsonValidationErrors('date');

        $this->assertSame('2026-12-01', $this->dateOf($vacation->fresh()));
    }

    #[Test]
    public function freelancer_vacation_delete_removes_only_this_entry(): void
    {
        $vacation = $this->createFreelancerVacation();
        $other = $this->createFreelancerVacation('2026-12-02');

        $this->delete(route('freelancer.vacation.delete', $vacation))->assertRedirect();

        $this->assertNull($vacation->fresh());
        $this->assertNotNull($other->fresh());
    }

    private function createFreelancerVacation(string $date = '2026-12-01'): Vacation
    {
        return Vacation::factory()->create([
            'vacationer_type' => Freelancer::class,
            'vacationer_id' => $this->freelancer->id,
            'date' => $date,
            'full_day' => true,
            'is_series' => false,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Vacation>
     */
    private function freelancerVacations(): \Illuminate\Support\Collection
    {
        return Vacation::query()
            ->where('vacationer_type', Freelancer::class)
            ->where('vacationer_id', $this->freelancer->id)
            ->orderBy('date')
            ->get();
    }

    private function dateOf(Vacation $vacation): string
    {
        return \Carbon\Carbon::parse($vacation->getRawOriginal('date'))->format('Y-m-d');
    }

    private function typeValue(Vacation $vacation): string
    {
        return $vacation->type instanceof VacationEnum ? $vacation->type->value : (string) $vacation->type;
    }
}
