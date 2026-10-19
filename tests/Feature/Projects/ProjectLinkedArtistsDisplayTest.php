<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Verknüpfte Künstler*innen (Sonderrolle „Künstler*in“ in CRM-Kontaktlisten bzw. Komponente
 * „Projektkünstler*in“) erscheinen überall, wo Projektkünstler*innen angezeigt werden – Freitext bleibt.
 */
final class ProjectLinkedArtistsDisplayTest extends TestCase
{
    use CrmContactListFixtures;

    #[Test]
    public function calendar_project_carries_text_and_linked_artists(): void
    {
        [$project, $contact] = $this->projectWithListArtist('Freie Gruppe', 'Compagnie Luna');

        $dto = ProjectDTO::fromModelForCalendar($project->fresh());

        $this->assertSame('Freie Gruppe, Compagnie Luna', $dto->artistNames);
        $this->assertSame([['id' => $contact->id, 'name' => 'Compagnie Luna']], $dto->linkedArtists);
    }

    #[Test]
    public function free_text_only_projects_stay_unchanged(): void
    {
        $project = Project::factory()->create(['artists' => 'Nur Freitext']);

        $this->assertSame('Nur Freitext', ProjectDTO::fromModelForCalendar($project->fresh())->artistNames);
        $this->assertSame('', Project::factory()->create(['artists' => ''])->fresh()->artistDisplayText());
    }

    #[Test]
    public function project_search_finds_artists_from_contact_lists(): void
    {
        [$project] = $this->projectWithListArtist('', 'Compagnie Luna');
        $this->actingAsAdmin();

        $this->getJson(route('projects.search', ['query' => 'luna']))
            ->assertOk()
            ->assertJsonFragment(['id' => $project->id, 'artists' => 'Compagnie Luna']);
    }

    #[Test]
    public function artist_tab_lists_list_artists_read_only(): void
    {
        [$project, $contact] = $this->projectWithListArtist('', 'Compagnie Luna');
        $this->actingAsAdmin();

        $this->getJson(route('projects.tabs.artist-name', $project))
            ->assertOk()
            ->assertJsonPath('list_artists.0.id', $contact->id)
            ->assertJsonPath('list_artists.0.via', ['Anreisende Personen']);
    }

    #[Test]
    public function project_viewers_may_open_the_artist_infocard(): void
    {
        [$project, $contact] = $this->projectWithListArtist('', 'Compagnie Luna');

        $outsider = User::factory()->create();
        $this->actingAs($outsider)->getJson(route('crm.contacts.tooltip', $contact))->assertForbidden();

        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => false]);
        $this->actingAs($member)->getJson(route('crm.contacts.tooltip', $contact))
            ->assertOk()
            ->assertJsonPath('display_name', 'Compagnie Luna');
    }

    /**
     * @return array{0: Project, 1: CrmContact}
     */
    private function projectWithListArtist(string $freeText, string $artistName): array
    {
        $context = $this->crmContactListContext();
        $project = $context['project'];
        $project->update(['artists' => $freeText]);
        $contact = $this->preLinkedContact($context, $artistName);
        ProjectComponentCrmContact::query()
            ->where('crm_contact_id', $contact->id)
            ->firstOrFail()
            ->update(['is_artist' => true]);

        return [$project, $contact];
    }
}
