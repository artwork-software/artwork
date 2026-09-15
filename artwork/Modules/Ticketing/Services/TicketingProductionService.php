<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Die Produktion eines Projekts in artwork tickets: was der Shop zeigt und welche Ermäßigungen
 * gelten. Bis zur ersten Freigabe nur ein Entwurf in artwork; danach wird jede Änderung sofort
 * nach tickets geschrieben.
 */
class TicketingProductionService
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketsClient $tickets,
    ) {
    }

    public function for(Project $project): TicketingProduction
    {
        return TicketingProduction::query()->firstOrNew(['project_id' => $project->id]);
    }

    /**
     * @param array{title: string|null, description: string|null, reduction_type_ids: list<string>|null} $data
     */
    public function save(Project $project, array $data, ?UploadedFile $hero, bool $removeHero): TicketingProduction
    {
        $production = $this->for($project);
        $production->fill($data);

        if ($hero || $removeHero) {
            if ($production->hero_path) {
                Storage::delete(TicketingProduction::HERO_DIRECTORY . '/' . $production->hero_path);
            }

            $production->hero_path = null;
            $production->hero_synced_at = null;
        }

        if ($hero) {
            $production->hero_path = StoredFileName::forUpload($hero);
            Storage::putFileAs(TicketingProduction::HERO_DIRECTORY, $hero, $production->hero_path);
        }

        $production->save();

        if ($production->production_id && ($connection = $this->connections->current())) {
            $this->push($production, $connection, $removeHero);
        }

        return $production;
    }

    /**
     * Legt die Produktion in tickets an oder bringt sie auf den Stand des Entwurfs.
     * Die Spielstätte zählt nur beim Anlegen; danach behält die Produktion ihren Stammraum.
     */
    public function ensure(Project $project, string $venueId, TicketingConnection $connection): TicketingProduction
    {
        $production = $this->for($project);

        $response = $this->tickets->put($connection, '/productions', $this->payload($production, $venueId));

        $production->production_id = $response['id'];
        $production->save();
        $this->pushHero($production, $connection, false);

        return $production;
    }

    /**
     * Stand in tickets für die Komponente; null, solange nichts freigegeben ist.
     *
     * @return array<string, mixed>|null
     */
    public function detail(TicketingProduction $production): ?array
    {
        $connection = $this->connections->current();

        if (!$production->production_id || !$connection) {
            return null;
        }

        return $this->tickets->get($connection, "/productions/{$production->production_id}");
    }

    private function push(TicketingProduction $production, TicketingConnection $connection, bool $removeHero): void
    {
        $this->tickets->put($connection, '/productions', $this->payload($production, null));
        $this->pushHero($production, $connection, $removeHero);
    }

    private function pushHero(TicketingProduction $production, TicketingConnection $connection, bool $remove): void
    {
        $path = "/productions/{$production->production_id}/hero";

        if ($remove && !$production->hero_path) {
            $this->tickets->delete($connection, $path);

            return;
        }

        if (!$production->hero_path || $production->hero_synced_at) {
            return;
        }

        $file = TicketingProduction::HERO_DIRECTORY . '/' . $production->hero_path;
        $this->tickets->putFile(
            $connection,
            $path,
            Storage::get($file) ?? throw new TicketingConnectionException(__('The picture could not be read.')),
            Storage::mimeType($file) ?: 'application/octet-stream',
            $production->hero_path,
        );
        $production->update(['hero_synced_at' => now()]);
    }

    /** @return array<string, mixed> */
    private function payload(TicketingProduction $production, ?string $venueId): array
    {
        $project = $production->project;

        return [
            'externalRef' => (string) $project->id,
            'title' => $production->title ?: $project->name,
            'description' => $production->description ?? $project->description ?? '',
            'venueId' => $venueId,
            'reductionTypeIds' => $production->reduction_type_ids,
        ];
    }
}
