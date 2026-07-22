<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Barryvdh\Snappy\PdfWrapper;
use Inertia\Testing\AssertableInertia;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class FreelancerControllerTest extends FeatureTestCase
{
    #[Test]
    public function show_provides_operational_plan_data_with_shifts_and_individual_times(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();

        $shift = Shift::factory()->create([
            'event_id' => Event::factory()->create([
                'start_time' => today()->setHour(10),
                'end_time' => today()->setHour(18),
            ])->id,
            'start_date' => today(),
            'end_date' => today(),
            'start' => '10:00',
            'end' => '18:00',
            'break_minutes' => 30,
        ]);
        $freelancer->shifts()->attach($shift->id, [
            'craft_abbreviation' => 'TST',
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ]);

        IndividualTime::create([
            'timeable_type' => Freelancer::class,
            'timeable_id' => $freelancer->id,
            'title' => 'Aufbau',
            'start_date' => today(),
            'end_date' => today(),
            'start_time' => '08:00',
            'end_time' => '09:30',
            'full_day' => false,
        ]);

        $today = today()->format('Y-m-d');

        $this->get(route('freelancer.show', $freelancer))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Freelancer/Show')
                ->has('crafts')
                ->has("daysWithData.{$today}.shifts", 1)
                ->has("daysWithData.{$today}.individualTimes", 1)
                ->where("daysWithData.{$today}.individualTimes.0.title", 'Aufbau'));
    }

    #[Test]
    public function shift_plan_pdf_export_works_for_freelancer_id_without_matching_user(): void
    {
        $this->actingAsAdmin();
        $this->mockPdfGenerator();

        // ID erzwingen, zu der garantiert kein User existiert – vorher lief die
        // Export-Route über User-Route-Model-Binding und antwortete hier mit 404.
        $freelancer = Freelancer::factory()->create();
        \Illuminate\Support\Facades\DB::table('freelancers')
            ->where('id', $freelancer->id)
            ->update(['id' => 987654]);

        $response = $this->post(route('user.shiftplan.export.monthly-pdf', 987654), [
            'type' => 'freelancer',
            'model_id' => 987654,
            'startMonth' => today()->format('Y-m'),
            'endMonth' => today()->format('Y-m'),
        ]);

        $response->assertRedirect();
    }

    private function mockPdfGenerator(): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')->once()->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('save')->once()->andReturnSelf();

        $this->app->instance(PdfWrapper::class, $pdf);
    }

    #[Test]
    public function guest_cannot_create_freelancer(): void
    {
        $this->post(route('freelancer.add'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_create_freelancer(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('freelancer.add'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302, 409]);
        $this->assertDatabaseHas('freelancers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('freelancer.add'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function admin_can_update_freelancer(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->patch(route('freelancer.update', $fl), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'position' => 'Engineer',
            'business' => 'Biz',
            'phone_number' => '123',
            'street' => 'Some St',
            'zip_code' => '12345',
            'location' => 'City',
            'note' => 'note',
        ]);

        $response->assertOk();
        $this->assertSame('New', $fl->fresh()->first_name);
    }

    #[Test]
    public function update_validates_required_fields(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->patch(route('freelancer.update', $fl), []);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function admin_can_destroy_freelancer(): void
    {
        $this->actingAsAdmin();
        $fl = Freelancer::factory()->create();

        $response = $this->delete(route('freelancer.destroy', $fl));

        $response->assertRedirect();
        $this->assertDatabaseMissing('freelancers', ['id' => $fl->id]);
    }

    #[Test]
    public function guest_cannot_destroy_freelancer(): void
    {
        $fl = Freelancer::factory()->create();
        $this->delete(route('freelancer.destroy', $fl))
            ->assertRedirect(route('login'));
    }
}
