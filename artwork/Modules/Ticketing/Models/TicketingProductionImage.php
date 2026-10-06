<?php

namespace Artwork\Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein weiteres Bild der Produktion für die Shopseite; liegt neben dem Hauptbild im selben Ordner.
 *
 * @property int $id
 * @property int $ticketing_production_id
 * @property string $path
 * @property string|null $remote_id
 */
class TicketingProductionImage extends Model
{
    protected $fillable = [
        'ticketing_production_id',
        'path',
        'remote_id',
    ];

    /** @return BelongsTo<TicketingProduction, $this> */
    public function production(): BelongsTo
    {
        return $this->belongsTo(TicketingProduction::class, 'ticketing_production_id');
    }

    public function url(): string
    {
        return '/storage/ticketing/' . $this->path;
    }

    public function storagePath(): string
    {
        return TicketingProduction::HERO_DIRECTORY . '/' . $this->path;
    }
}
