<?php

namespace Tests\Feature\Modules\Craft;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Datenvertrag der crafts-Prop für Projekt-Schichten-Tab und Schichtplan-Listenansicht.
 *
 * Die Personenauswahl (Klick auf einen unbesetzten Platz, Drag&Drop aus der Personenleiste)
 * liest aus den Personen je Gewerk nur eine feste Feldmenge; CraftService::getAllWithAssignableWorkers
 * liefert genau diese und nichts darüber hinaus (das volle User-Modell machte die Prop
 * mehrere MB groß). Bricht dieser Test, fehlt der Zuweisungs-UI ein Feld — oder das
 * Modell wird wieder komplett ausgeliefert.
 */
final class CraftAssignableWorkersPayloadTest extends FeatureTestCase
{
    /** Felder, die DragElement / SingleShiftInDailyShiftView / ShiftBookedElementComponent lesen */
    private const PERSON_FIELDS = ['id', 'profile_photo_url', 'can_work_shifts', 'type', 'shift_qualifications'];

    private Craft $craft;
    private ShiftQualification $qualification;
    private User $worker;
    private Freelancer $freelancer;
    private ServiceProvider $serviceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->craft = Craft::factory()->create();
        $this->qualification = ShiftQualification::factory()->create();

        $this->worker = User::factory()->create(['can_work_shifts' => true]);
        $this->worker->assignedCrafts()->attach($this->craft->id);
        $this->worker->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);

        // Ohne can_work_shifts gehört die Person nicht in die Auswahlliste (Craft::users()-Scope)
        $noShifts = User::factory()->create(['can_work_shifts' => false]);
        $noShifts->assignedCrafts()->attach($this->craft->id);

        $this->freelancer = Freelancer::factory()->create(['can_work_shifts' => true]);
        $this->freelancer->assignedCrafts()->attach($this->craft->id);
        $this->freelancer->shiftQualifications()->attach($this->qualification->id, ['craft_id' => $this->craft->id]);

        $this->serviceProvider = ServiceProvider::factory()->create(['can_work_shifts' => true]);
        $this->serviceProvider->assignedCrafts()->attach($this->craft->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializedCraft(): array
    {
        $crafts = app(CraftService::class)->getAllWithAssignableWorkers()->toArray();

        $craft = collect($crafts)->firstWhere('id', $this->craft->id);
        $this->assertNotNull($craft, 'Gewerk fehlt in der crafts-Liste');

        return $craft;
    }

    /**
     * @param array<string, mixed> $person
     */
    private function assertPersonContract(array $person, string $label): void
    {
        foreach (self::PERSON_FIELDS as $field) {
            $this->assertArrayHasKey($field, $person, "$label: Feld '$field' fehlt");
        }

        $this->assertTrue((bool) $person['can_work_shifts'], "$label: can_work_shifts");
        $this->assertNotSame('', (string) $person['profile_photo_url'], "$label: profile_photo_url leer");
    }

    /**
     * @param array<int, array<string, mixed>> $qualifications
     */
    private function assertQualificationContract(array $qualifications, string $label): void
    {
        $this->assertCount(1, $qualifications, "$label: Funktion fehlt");
        $this->assertSame($this->qualification->id, $qualifications[0]['id']);
        $this->assertArrayHasKey('name', $qualifications[0]);
        // Zuordnung Funktion → Gewerk: darüber entscheidet die Auswahlliste, ob eine
        // Person direkt oder über ein Universalgewerk zuweisbar ist
        $this->assertSame($this->craft->id, $qualifications[0]['pivot']['craft_id'], "$label: pivot.craft_id");
    }

    #[Test]
    public function users_carry_exactly_the_fields_of_the_assignment_ui(): void
    {
        $craft = $this->serializedCraft();

        $this->assertCount(1, $craft['users'], 'nur Personen mit can_work_shifts');
        $user = $craft['users'][0];

        $this->assertSame($this->worker->id, $user['id']);
        $this->assertPersonContract($user, 'User');
        $this->assertSame($this->worker->first_name, $user['first_name']);
        $this->assertSame($this->worker->last_name, $user['last_name']);
        $this->assertSame('user', $user['type']);
        $this->assertArrayHasKey('is_freelancer', $user);
        $this->assertQualificationContract($user['shift_qualifications'], 'User');

        // Interna bleiben draußen — sonst wächst die Prop wieder auf das volle Modell
        $internal = ['email', 'password', 'remember_token', 'calendar_settings', 'notification_enums_last_sent_dates'];
        foreach ($internal as $hidden) {
            $this->assertArrayNotHasKey($hidden, $user, "User: '$hidden' darf nicht ausgeliefert werden");
        }
    }

    #[Test]
    public function freelancers_and_service_providers_carry_name_and_assigned_craft_ids(): void
    {
        $craft = $this->serializedCraft();

        $this->assertCount(1, $craft['freelancers']);
        $freelancer = $craft['freelancers'][0];
        $this->assertSame($this->freelancer->id, $freelancer['id']);
        $this->assertPersonContract($freelancer, 'Freelancer');
        $this->assertSame($this->freelancer->first_name, $freelancer['first_name']);
        $this->assertSame($this->freelancer->last_name, $freelancer['last_name']);
        $this->assertSame('freelancer', $freelancer['type']);
        $this->assertSame([$this->craft->id], $freelancer['assigned_craft_ids']);
        $this->assertQualificationContract($freelancer['shift_qualifications'], 'Freelancer');

        $this->assertCount(1, $craft['service_providers']);
        $provider = $craft['service_providers'][0];
        $this->assertSame($this->serviceProvider->id, $provider['id']);
        $this->assertPersonContract($provider, 'ServiceProvider');
        $this->assertSame($this->serviceProvider->provider_name, $provider['provider_name']);
        $this->assertSame('service_provider', $provider['type']);
        $this->assertSame([$this->craft->id], $provider['assigned_craft_ids']);
    }

    #[Test]
    public function serializing_the_crafts_runs_no_query_per_person(): void
    {
        // Weitere Externe: früher je Person eine craftables-Query beim Serialisieren
        Freelancer::factory()->count(5)->create(['can_work_shifts' => true])
            ->each(fn (Freelancer $f) => $f->assignedCrafts()->attach($this->craft->id));
        ServiceProvider::factory()->count(5)->create(['can_work_shifts' => true])
            ->each(fn (ServiceProvider $sp) => $sp->assignedCrafts()->attach($this->craft->id));

        $crafts = app(CraftService::class)->getAllWithAssignableWorkers();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $crafts->toArray();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(
            0,
            $queries,
            'Serialisierung darf keine Queries auslösen: ' . json_encode(array_column($queries, 'query'))
        );
    }

    /**
     * Planer:innen des Gewerks bleiben dabei — schlank: die Listenansicht erkennt darüber
     * die Planer:in (Personen-Menü an der Schicht), das Zeitanpassungs-Modal zeigt Name,
     * Position, Firma und Avatar.
     */
    #[Test]
    public function craft_planers_are_shipped_slim(): void
    {
        $planer = User::factory()->create(['position' => 'Disposition', 'business' => 'Haus']);
        $this->craft->craftShiftPlaner()->attach($planer->id);

        $craft = $this->serializedCraft();

        $this->assertArrayHasKey('qualifications', $craft);
        $this->assertCount(1, $craft['craft_shift_planer']);
        $shipped = $craft['craft_shift_planer'][0];
        $this->assertEqualsCanonicalizing(CraftService::PLANER_VISIBLE, array_keys($shipped));
        $this->assertSame($planer->id, $shipped['id']);
        $this->assertSame($planer->first_name . ' ' . $planer->last_name, $shipped['full_name']);
        $this->assertSame('Disposition', $shipped['position']);
        $this->assertSame('Haus', $shipped['business']);

        // Lookup-Variante der Einsatzplan-Seiten: gleiche Planer-Form
        $lookup = collect(app(CraftService::class)->getLookupCrafts()->toArray())->firstWhere('id', $this->craft->id);
        $this->assertSame(
            ['id', 'name', 'abbreviation', 'color', 'position', 'universally_applicable', 'craft_shift_planer'],
            array_keys($lookup)
        );
        $this->assertEqualsCanonicalizing(CraftService::PLANER_VISIBLE, array_keys($lookup['craft_shift_planer'][0]));
    }

    #[Test]
    public function project_shift_tab_delivers_the_crafts_prop_with_this_contract(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Schichten',
            'type' => ProjectTabComponentEnum::SHIFT_TAB->value,
            'data' => [],
        ]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('crafts')
                ->where('crafts', fn ($crafts) => $this->craftsPropSatisfiesContract(collect($crafts)->toArray())));
    }

    #[Test]
    public function shift_plan_list_view_delivers_the_crafts_prop_with_this_contract(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.plan.list-view'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('crafts')
                ->where('crafts', fn ($crafts) => $this->craftsPropSatisfiesContract(collect($crafts)->toArray())));
    }

    /**
     * @param array<int, array<string, mixed>> $crafts
     */
    private function craftsPropSatisfiesContract(array $crafts): bool
    {
        $craft = collect($crafts)->firstWhere('id', $this->craft->id);
        $this->assertNotNull($craft, 'Gewerk fehlt in der crafts-Prop');

        $user = collect($craft['users'])->firstWhere('id', $this->worker->id);
        $this->assertNotNull($user, 'User fehlt in crafts[].users');
        $this->assertPersonContract($user, 'Prop-User');
        $this->assertQualificationContract($user['shift_qualifications'], 'Prop-User');
        $this->assertArrayNotHasKey('email', $user);

        $freelancer = collect($craft['freelancers'])->firstWhere('id', $this->freelancer->id);
        $this->assertNotNull($freelancer, 'Freelancer fehlt in crafts[].freelancers');
        $this->assertSame([$this->craft->id], $freelancer['assigned_craft_ids']);

        $provider = collect($craft['service_providers'])->firstWhere('id', $this->serviceProvider->id);
        $this->assertNotNull($provider, 'Dienstleister fehlt in crafts[].service_providers');
        $this->assertSame($this->serviceProvider->provider_name, $provider['provider_name']);
        $this->assertSame([$this->craft->id], $provider['assigned_craft_ids']);

        return true;
    }
}
