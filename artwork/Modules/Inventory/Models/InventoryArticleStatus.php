<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Once;

class InventoryArticleStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'order'
    ];

    protected $casts = [
        'default' => 'boolean',
        'deletable' => 'boolean',
    ];

    protected static function booted(): void
    {
        // defaultStatusId() ist je Request gemerkt; Änderungen an Status sofort sichtbar machen
        static::saved(static fn () => Once::flush());
        static::deleted(static fn () => Once::flush());
    }

    /**
     * Der Status, dessen Menge als verfügbar gilt („Einsatzbereit“ im Auslieferungszustand).
     * Maßgeblich ist das default-Flag, nicht der Name – Status sind in den Einstellungen
     * umbenennbar, ein Namensvergleich ließ die Verfügbarkeit dann überall auf 0 fallen.
     */
    public static function defaultStatusId(): ?int
    {
        return once(static function (): ?int {
            $id = static::query()->where('default', true)->orderBy('order')->orderBy('id')->value('id');

            return $id === null ? null : (int) $id;
        });
    }
}
