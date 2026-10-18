<?php

namespace Tests\Feature\PropShape;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Seiten, die Personen nur als Avatar mit Tooltip zeigen, liefern sie schlank aus (Kontaktdaten lädt der
 * Tooltip per lazy-load nach) — volle User-Modelle (rund 90 Spalten) vervielfachten die Payloads.
 * Die Felder, die die Oberfläche liest, müssen erhalten bleiben.
 */
final class PersonPayloadTest extends FeatureTestCase
{
    private const PERSON_FIELDS = ['first_name', 'full_name', 'id', 'last_name', 'profile_photo_url'];

    /**
     * @return array<string, mixed>
     */
    private function props(string $url): array
    {
        $props = $this->get($url)->assertOk()->viewData('page')['props'];

        return json_decode((string) json_encode($props), true);
    }

    /**
     * @param array<string, mixed> $person
     */
    private function assertSlimPerson(array $person, User $expected): void
    {
        $keys = array_keys($person);
        sort($keys);
        $this->assertSame(self::PERSON_FIELDS, $keys);
        $this->assertSame($expected->id, $person['id']);
        $this->assertSame($expected->full_name, $person['full_name']);
    }

    #[Test]
    public function material_issues_deliver_slim_responsible_people_and_rooms_without_admins(): void
    {
        $this->actingAsAdmin();
        $responsible = User::factory()->create();
        $room = Room::factory()->create();
        $room->admins()->attach(User::factory()->create()->id, ['is_admin' => true]);
        $issue = InternalIssue::factory()->create([
            'room_id' => $room->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
        $issue->responsibleUsers()->attach($responsible->id);

        $props = $this->props(route('issue-of-material.index'));
        $delivered = collect($props['issues']['data'])->firstWhere('id', $issue->id);

        $this->assertNotNull($delivered);
        $this->assertSame($room->name, $delivered['room']['name']);
        $this->assertSame($room->id, $delivered['room']['id']);
        $this->assertArrayNotHasKey('admins', $delivered['room']);
        $this->assertArrayNotHasKey('creator', $delivered['room']);
        $this->assertCount(1, $delivered['responsible_users']);
        $this->assertSlimPerson($delivered['responsible_users'][0], $responsible);
    }

    #[Test]
    public function dashboard_rooms_carry_admin_ids_for_the_event_dialog_only(): void
    {
        $this->actingAsAdmin();
        $admin = User::factory()->create();
        $room = Room::factory()->create();
        $room->admins()->attach($admin->id, ['is_admin' => true]);

        $props = $this->props('/dashboard');
        $delivered = collect($props['rooms'])->firstWhere('id', $room->id);

        $this->assertNotNull($delivered);
        $this->assertSame([['id' => $admin->id]], $delivered['admins']);
        $this->assertArrayNotHasKey('creator', $delivered);
    }

    #[Test]
    public function checklist_templates_on_own_tasks_deliver_a_slim_creator(): void
    {
        $this->actingAsAdmin();
        $template = ChecklistTemplate::factory()->create();

        $props = $this->props(route('tasks.own'));
        $delivered = collect($props['checklist_templates'])->firstWhere('id', $template->id);

        $this->assertNotNull($delivered);
        $this->assertSlimPerson($delivered['user'], $template->user);
    }
}
