<?php

namespace Artwork\Modules\Holidays\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $country_code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Subdivision extends Model
{
    public const BRANDENBURG = 'BB';
    public const BERLIN = 'BE';
    public const BADEN_WUERTTEMBERG = 'BW';
    public const BAYERN = 'BY';
    public const BREMEN = 'HB';
    public const HESSEN = 'HE';
    public const HAMBURG = 'HH';
    public const MECKLENBURG_VORPOMMERN = 'MV';
    public const MECKLENBURG_VORPOMMERN_ABS = 'MV-ABS';
    public const MECKLENBURG_VORPOMMERN_BBS = 'MV-BBS';
    public const NIEDERSACHSEN = 'NI';
    public const NORDRHEIN_WESTFALEN = 'NW';
    public const RHEINLAND_PFALZ = 'RP';
    public const SCHLESWIG_HOLSTEIN = 'SH';
    public const SAARLAND = 'SL';
    public const SACHSEN = 'SN';
    public const SACHSEN_ANHALT = 'ST';
    public const THUERINGEN = 'TH';



    protected $table = 'subdivisions';

    protected $guarded = [];

    public function holidays(): BelongsToMany
    {
        return $this->belongsToMany(
            Holiday::class,
            'holidays_subdivisions',
            'subdivision_id',
            'holiday_id'
        );
    }

    /**
     * @return string[]
     */
    public static function codes(): array
    {
        return [
            'Brandenburg' => static::BRANDENBURG,
            'Berlin' => static::BERLIN,
            'Baden-Württemberg' => static::BADEN_WUERTTEMBERG,
            'Bayern' => static::BAYERN,
            'Bremen' => static::BREMEN,
            'Hessen' => static::HESSEN,
            'Hamburg' => static::HAMBURG,
            'Mecklenburg-Vorpommern' => static::MECKLENBURG_VORPOMMERN,
            'Mecklenburg-Vorpommern Allgemeinbildende Schulen' => static::MECKLENBURG_VORPOMMERN_ABS,
            'Mecklenburg-Vorpommern Berufsbildende Schulen' => static::MECKLENBURG_VORPOMMERN_BBS,
            'Niedersachsen' => static::NIEDERSACHSEN,
            'Nordrhein-Westfalen' => static::NORDRHEIN_WESTFALEN,
            'Rheinland-Pfalz' => static::RHEINLAND_PFALZ,
            'Schleswig-Holstein' => static::SCHLESWIG_HOLSTEIN,
            'Saarland' => static::SAARLAND,
            'Sachsen' => static::SACHSEN,
            'Sachsen-Anhalt' => static::SACHSEN_ANHALT,
            'Thüringen' => static::THUERINGEN,
        ];
    }
}
