<?php

namespace Artwork\Modules\Ticketing\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Wie ein Projekt im Ticketshop auftritt. Leere Felder fallen auf das Projekt zurück
 * (Name, Beschreibung); reduction_type_ids null heißt: die Standard-Ermäßigungen des Hauses.
 *
 * @property int $id
 * @property int $project_id
 * @property string|null $production_id
 * @property string|null $title
 * @property string|null $description
 * @property list<string>|null $reduction_type_ids
 * @property string|null $hero_path
 * @property \Carbon\Carbon|null $hero_synced_at
 * @property-read Collection<int, TicketingProductionImage> $images
 */
class TicketingProduction extends Model
{
    public const HERO_DIRECTORY = 'public/ticketing';

    /** So viele weitere Bilder nimmt tickets je Produktion an. */
    public const MAX_IMAGES = 12;

    protected $fillable = [
        'project_id',
        'production_id',
        'title',
        'description',
        'reduction_type_ids',
        'hero_path',
        'hero_synced_at',
    ];

    protected $casts = [
        'reduction_type_ids' => 'array',
        'hero_synced_at' => 'datetime',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<TicketingProductionImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(TicketingProductionImage::class)->orderBy('id');
    }

    public function heroUrl(): ?string
    {
        return $this->hero_path ? '/storage/ticketing/' . $this->hero_path : null;
    }
}
