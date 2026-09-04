<?php

namespace Artwork\Modules\Holidays\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $date
 * @property Carbon $end_date
 * @property int|null $rota
 * @property string|null $country
 * @property Subdivision[]|Collection $subdivisions
 * @property string|null $remote_identifier
 * @property bool $from_api
 * @property string $type public|school|custom
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Holiday extends Model
{
    /** Gesetzlicher Feiertag (OpenHolidays "Public") – Sondertag-Default: ja */
    public const TYPE_PUBLIC = 'public';
    /** Schulferien (OpenHolidays "School") – Sondertag-Default: nein */
    public const TYPE_SCHOOL = 'school';
    /** Manuell angelegter Eintrag */
    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [self::TYPE_PUBLIC, self::TYPE_SCHOOL, self::TYPE_CUSTOM];

    protected $table = 'holidays';

    protected $fillable = [
        'name',
        'date',
        'end_date',
        'rota',
        'country',
        'remote_identifier',
        'from_api',
        'type',
        'yearly',
        'color',
        'treatAsSpecialDay',
    ];

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'from_api' => 'boolean',
        'type' => 'string',
        'yearly' => 'boolean',
        'treatAsSpecialDay' => 'boolean',
    ];

    protected $appends = [
        'casted_date',
    ];

    public function subdivisions(): BelongsToMany
    {
        return $this->belongsToMany(
            Subdivision::class,
            'holidays_subdivisions',
            'holiday_id',
            'subdivision_id'
        );
    }

    /**
     * Sondertag-Prüfung. Einzige Quelle ist der SpecialDayService (Flag, mehrtägige Einträge,
     * jährliche Wiederholung); diese Methode bleibt als Fassade für Altaufrufer.
     */
    public static function isSpecialDay(Carbon|string $date): bool
    {
        return app(\Artwork\Modules\Holidays\Services\SpecialDayService::class)->isSpecialDay($date);
    }

    /**
     * Typ normalisieren (unbekannte Werte -> custom).
     */
    public static function normalizeType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, self::TYPES, true) ? $type : self::TYPE_CUSTOM;
    }

    /**
     * Sondertag-Default je Typ: nur gesetzliche Feiertage sind standardmäßig Sondertage.
     */
    public static function defaultTreatAsSpecialDayFor(?string $type): bool
    {
        return self::normalizeType($type) === self::TYPE_PUBLIC;
    }

    /**
     * @return string[]
     */
    public function getCastedDateAttribute(): array
    {
        return [
            'date' => $this->date->translatedFormat('l, jS F Y'),
            'end_date' => $this->end_date->translatedFormat('l, jS F Y'),
        ];
    }
}
