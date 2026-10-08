<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingProductionImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Die Produktion eines Projekts in Artwork-Tickets: was der Shop zeigt und welche Ermäßigungen
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

    /** Die Bilddateien eines endgültig gelöschten Projekts; die Zeilen nimmt die Kaskade der Datenbank mit. */
    public function deleteFilesOf(Project $project): void
    {
        $production = TicketingProduction::query()->with('images')->where('project_id', $project->id)->first();

        if ($production === null) {
            return;
        }

        $paths = $production->images->map(static fn (TicketingProductionImage $image): string => $image->storagePath());
        if ($production->hero_path) {
            $paths->push(TicketingProduction::HERO_DIRECTORY . '/' . $production->hero_path);
        }

        Storage::delete($paths->unique()->all());
    }

    /**
     * @param array{title: string|null, description: string|null, reduction_type_ids: list<string>|null} $data
     * Ein neues Titelbild ($hero oder das weitere Bild $coverImageId) schiebt das bisherige zu den
     * weiteren Bildern, außer $removeHero nimmt es weg.
     *
     * @param list<UploadedFile> $images weitere Bilder, hinten angefügt
     * @param list<int> $removeImageIds weitere Bilder, die herausfallen
     */
    public function save(
        Project $project,
        array $data,
        ?UploadedFile $hero,
        bool $removeHero,
        array $images = [],
        array $removeImageIds = [],
        ?int $coverImageId = null,
    ): TicketingProduction {
        $production = $this->for($project);
        $production->fill($data);
        $previousHero = $production->hero_path;
        $cover = $coverImageId === null ? null : $production->images()->findOrFail($coverImageId);

        if ($hero || $cover || $removeHero) {
            if ($previousHero && $removeHero) {
                Storage::delete(TicketingProduction::HERO_DIRECTORY . '/' . $previousHero);
            }

            $production->hero_path = $cover?->path;
            $production->hero_synced_at = null;
        }

        if ($hero) {
            $production->hero_path = StoredFileName::forUpload($hero);
            Storage::putFileAs(TicketingProduction::HERO_DIRECTORY, $hero, $production->hero_path);
        }

        $production->save();

        $removedRemoteIds = $this->removeImages($production, $removeImageIds);

        if ($cover) {
            // Die Datei bleibt liegen, sie ist jetzt das Titelbild; tickets führt sie nicht mehr als weiteres Bild.
            if ($cover->remote_id) {
                $removedRemoteIds[] = $cover->remote_id;
            }

            $cover->delete();
        }

        if ($previousHero && !$removeHero && ($hero || $cover)) {
            $production->images()->create(['path' => $previousHero]);
        }

        foreach ($images as $image) {
            $path = StoredFileName::forUpload($image);
            Storage::putFileAs(TicketingProduction::HERO_DIRECTORY, $image, $path);
            $production->images()->create(['path' => $path]);
        }

        if ($production->production_id && ($connection = $this->connections->current())) {
            $this->push($production, $connection, $removeHero, $removedRemoteIds);
        }

        return $production;
    }

    /**
     * Löscht die Bilder hier und gibt zurück, welche davon tickets schon kennt.
     *
     * @param list<int> $ids
     * @return list<string>
     */
    private function removeImages(TicketingProduction $production, array $ids): array
    {
        $removed = $production->images()->whereIn('id', $ids)->get();

        foreach ($removed as $image) {
            Storage::delete($image->storagePath());
            $image->delete();
        }

        return $removed->pluck('remote_id')->filter()->values()->all();
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
        $this->pushImages($production, $connection, []);

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

    /** @param list<string> $removedRemoteIds */
    private function push(
        TicketingProduction $production,
        TicketingConnection $connection,
        bool $removeHero,
        array $removedRemoteIds,
    ): void {
        $this->tickets->put($connection, '/productions', $this->payload($production, null));
        $this->pushHero($production, $connection, $removeHero);
        $this->pushImages($production, $connection, $removedRemoteIds);
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
            $this->read($file),
            Storage::mimeType($file) ?: 'application/octet-stream',
            $production->hero_path,
        );
        $production->update(['hero_synced_at' => now()]);
    }

    /**
     * Nimmt in tickets heraus, was hier gelöscht wurde, und reicht nach, was dort noch fehlt.
     *
     * @param list<string> $removedRemoteIds
     */
    private function pushImages(
        TicketingProduction $production,
        TicketingConnection $connection,
        array $removedRemoteIds,
    ): void {
        $base = "/productions/{$production->production_id}/images";

        foreach ($removedRemoteIds as $remoteId) {
            $this->tickets->delete($connection, "{$base}/{$remoteId}");
        }

        foreach ($production->images()->whereNull('remote_id')->get() as $image) {
            $response = $this->tickets->postFile(
                $connection,
                $base,
                $this->read($image->storagePath()),
                Storage::mimeType($image->storagePath()) ?: 'application/octet-stream',
                $image->path,
            );
            $image->update(['remote_id' => $response['id']]);
        }
    }

    private function read(string $file): string
    {
        return Storage::get($file) ?? throw new TicketingConnectionException(__('The picture could not be read.'));
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
