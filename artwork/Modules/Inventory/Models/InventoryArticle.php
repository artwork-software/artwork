<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Artwork\Modules\Inventory\Services\TypeNumberGenerator;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $name
 * @property string $description
 * @property int $inventory_category_id
 * @property int $inventory_sub_category_id
 * @property int $quantity
 * @property bool $is_detailed_quantity
 * @property string $external_id
 * @property string $inventory_number
 * @property \Illuminate\Database\Eloquent\Collection<int,
 *     \Artwork\Modules\Inventory\Models\InventoryArticleProperty> properties
 * @property \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Inventory\Models\InventoryArticleImage[] images
 * @property \Artwork\Modules\Inventory\Models\InventoryCategory $category
 * @property \Artwork\Modules\Inventory\Models\InventorySubCategory subCategory
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @extends \Illuminate\Database\Eloquent\Model
 * @uses \Illuminate\Database\Eloquent\Factories\HasFactory
 * @uses \Artwork\Modules\Inventory\Models\InventoryArticleFactory
 * @property-read InventoryCategory|null $category
 * @property-read InventorySubCategory|null $subCategory
 */
class InventoryArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'inventory_category_id',
        'inventory_sub_category_id',
        'quantity',
        'is_detailed_quantity',
        'external_id',
        'inventory_number',
    ];

    protected $casts = [
        'is_detailed_quantity' => 'boolean',
        'quantity' => 'integer',
        'inventory_category_id' => 'integer',
        'inventory_sub_category_id' => 'integer',
        'created_at' => TranslatedDateTimeCast::class,
        'updated_at' => TranslatedDateTimeCast::class,
        'deleted_at' => TranslatedDateTimeCast::class,
    ];

    protected $appends = ['room', 'manufacturer', 'category', 'subCategory'];

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (InventoryArticle $article): void {
            if (!$article->external_id) {
                $article->external_id = TypeNumberGenerator::generateExternalId();
            }
            if (!$article->inventory_number) {
                $article->inventory_number = TypeNumberGenerator::generateInventoryNumber();
            }
        });
    }
    /**
     * @return BelongsTo<InventoryCategory, $this>
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id', 'id');
    }

    /**
     * @return BelongsTo<InventorySubCategory, $this>
     */
    public function subCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventorySubCategory::class, 'inventory_sub_category_id', 'id');
    }

    /**
     * @return HasMany<InventoryArticleImage, $this>
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryArticleImage::class, 'inventory_article_id', 'id');
    }

    /**
     * @return HasMany<InventoryDetailedQuantityArticle, $this>
     */
    public function detailedArticleQuantities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryDetailedQuantityArticle::class, 'inventory_article_id', 'id');
    }

    /**
     * @return BelongsToMany<InventoryArticleStatus, $this>
     */
    public function statusValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            InventoryArticleStatus::class,
            'inventory_article_status_values',
            'inventory_article_id',
            'inventory_article_status_id'
        )->withPivot('value')->orderBy('order');
    }

    /**
     * @return MorphToMany<InternalIssue, $this>
     */
    public function internalIssues(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(InternalIssue::class, 'issuable', 'issuable_inventory_article')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<ExternalIssue, $this>
     */
    public function externalIssues(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(ExternalIssue::class, 'issuable', 'issuable_inventory_article')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function searchableAs(): string
    {
        return 'inventory_articles';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Name not found',
            'inventory_number' => $this->inventory_number,
            'description' => $this->description,
            'category' => $this?->category?->name ?? null,
            'sub_category' => $this?->subCategory?->name ?? null,
            'quantity' => $this->quantity ?? 0,
            'room' => $this?->room['name'] ?? null,
            'manufacturer' => $this?->manufacturer['name'] ?? null,
            'properties' => $this?->properties?->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'value' => $property->pivot->value
                ];
            })->toArray() ?? [],
        ];
    }

    public function getCategoryAttribute(): ?InventoryCategory
    {
        return $this->getRelationValue('category');
    }

    public function getSubCategoryAttribute(): ?InventorySubCategory
    {
        return $this->getRelationValue('subCategory');
    }

    /**
     * Lookup-Caches der $appends room/manufacturer (Id => Modell|null), je Prozess.
     * array_key_exists statt isset: ein gecachtes null gilt bei isset() nicht als
     * vorhanden, wodurch nicht auflösbare Werte bei JEDER Serialisierung erneut
     * abgefragt wurden (gemessen 100x dieselbe Query auf /inventory).
     *
     * @var array<int|string, Room|null>
     */
    private static array $roomCache = [];

    /** @var array<int|string, CrmContact|null> */
    private static array $manufacturerCache = [];

    /** Caches leeren (Tests; nach Umbenennung eines Herstellers/Raums in langlebigen Prozessen) */
    public static function flushPropertyLookupCaches(): void
    {
        self::$roomCache = [];
        self::$manufacturerCache = [];
    }

    public function newCollection(array $models = []): InventoryArticleCollection
    {
        return new InventoryArticleCollection($models);
    }

    /**
     * Räume und Hersteller aller Artikel in je einer Query vorladen (InventoryArticleCollection
     * ruft das vor dem Serialisieren) — sonst eine Query je unterschiedlichem Wert.
     *
     * @param iterable<int, InventoryArticle> $articles
     */
    /**
     * Lookups für alle Artikel in einer verschachtelten Struktur (Kategorien → Unterkategorien
     * → Artikel) auf einmal vorladen — die Collection-Variante greift sonst je Kategorie einzeln.
     */
    public static function preloadPropertyLookupsDeep(mixed $value): void
    {
        $articles = [];
        self::collectArticles($value, $articles, 0);
        self::preloadPropertyLookups($articles);
    }

    /**
     * @param array<int, InventoryArticle> $articles
     */
    private static function collectArticles(mixed $value, array &$articles, int $depth): void
    {
        if ($depth > 6) {
            return;
        }
        if ($value instanceof self || $value instanceof InventoryDetailedQuantityArticle) {
            $articles[] = $value;
        }
        if ($value instanceof Model) {
            foreach ($value->getRelations() as $relation) {
                self::collectArticles($relation, $articles, $depth + 1);
            }

            return;
        }
        if (is_iterable($value)) {
            foreach ($value as $item) {
                self::collectArticles($item, $articles, $depth + 1);
            }
        }
    }

    /**
     * @param iterable<int, InventoryArticle|InventoryDetailedQuantityArticle> $articles
     */
    public static function preloadPropertyLookups(iterable $articles): void
    {
        $roomIds = [];
        $manufacturerIds = [];
        foreach ($articles as $article) {
            if (
                !($article instanceof self || $article instanceof InventoryDetailedQuantityArticle)
                || !$article->relationLoaded('properties')
            ) {
                continue;
            }
            foreach ($article->properties as $property) {
                $value = $property->pivot->value ?? null;
                if (!$value) {
                    continue;
                }
                if ($property->type === 'room') {
                    $roomIds[$value] = true;
                } elseif ($property->type === 'manufacturer') {
                    $manufacturerIds[$value] = true;
                }
            }
        }

        $missingRooms = array_keys(array_diff_key($roomIds, self::$roomCache));
        if ($missingRooms !== []) {
            $rooms = Room::query()
                ->without(['admins', 'creator'])
                ->select('id', 'name')
                ->whereIn('id', $missingRooms)
                ->get()
                ->keyBy('id');
            foreach ($missingRooms as $id) {
                self::$roomCache[$id] = $rooms->get($id);
            }
        }

        $missingManufacturers = array_keys(array_diff_key($manufacturerIds, self::$manufacturerCache));
        if ($missingManufacturers !== []) {
            $contacts = CrmContact::query()
                ->select('id', 'display_name')
                ->whereIn('id', $missingManufacturers)
                ->get()
                ->keyBy('id');
            foreach ($missingManufacturers as $id) {
                self::$manufacturerCache[$id] = $contacts->get($id);
            }
        }
    }

    /** Raum aus dem Cache (bei Bedarf einzeln nachgeladen) — auch für Detail-Artikel */
    public static function resolveRoom(int|string $roomId): ?Room
    {
        if (!array_key_exists($roomId, self::$roomCache)) {
            self::$roomCache[$roomId] = Room::query()
                ->without(['admins', 'creator'])
                ->select('id', 'name')
                ->find($roomId);
        }

        return self::$roomCache[$roomId];
    }

    /** Hersteller aus dem Cache (bei Bedarf einzeln nachgeladen) — auch für Detail-Artikel */
    public static function resolveManufacturer(int|string $manufacturerId): ?CrmContact
    {
        if (!array_key_exists($manufacturerId, self::$manufacturerCache)) {
            self::$manufacturerCache[$manufacturerId] = CrmContact::select('id', 'display_name')->find($manufacturerId);
        }

        return self::$manufacturerCache[$manufacturerId];
    }

    public function getRoomAttribute(): ?array
    {
        $roomProperty = $this->properties->firstWhere('type', 'room');

        if (!$roomProperty || !$roomProperty->pivot->value) {
            return null;
        }

        $room = self::resolveRoom($roomProperty->pivot->value);

        if (!$room) {
            return null;
        }

        return [
            'id' => $room->id,
            'name' => $room->name,
            'property_id' => $roomProperty->id,
        ];
    }

    public function getManufacturerAttribute(): ?array
    {
        $manufacturerProperty = $this->properties->firstWhere('type', 'manufacturer');

        if (!$manufacturerProperty || !$manufacturerProperty->pivot->value) {
            return null;
        }

        $manufacturer = self::resolveManufacturer($manufacturerProperty->pivot->value);

        if (!$manufacturer) {
            return null;
        }

        return [
            'id' => $manufacturer->id,
            'name' => $manufacturer->display_name,
            'property_id' => $manufacturerProperty->id,
        ];
    }

    public function getAvailableStock(
        string $startDate,
        string $endDate,
        ?int $excludeIssueId = null,
        ?string $excludeType = null
    ): array {
        // SQL-Vorauswahl nur nach Datum: ein Vergleich der Datumsspalten mit "Y-m-d H:i" schloss
        // Ausgaben desselben Tages aus. Die Uhrzeit-Genauigkeit kommt über das Zeitfenster unten.
        $startDay = Carbon::parse($startDate)->toDateString();
        $endDay = Carbon::parse($endDate)->toDateString();

        if ($this->relationLoaded('internalIssues')) {
            $internalIssues = $this->internalIssues;
            // Apply exclusion filter to pre-loaded relations if needed
            if ($excludeType === 'intern' && $excludeIssueId) {
                $internalIssues = $internalIssues->filter(function ($issue) use ($excludeIssueId) {
                    return $issue->id !== $excludeIssueId;
                });
            }
        } else {
            $internalIssues = $this->internalIssues()
                ->where('start_date', '<=', $endDay)
                ->where(function ($q) use ($startDay): void {
                    $q->where('end_date', '>=', $startDay)
                        ->orWhereNull('end_date');
                })
                ->when($excludeType === 'intern' && $excludeIssueId, function ($q) use ($excludeIssueId): void {
                    $q->where('internal_issues.id', '!=', $excludeIssueId);
                })
                ->get();
        }

        if ($this->relationLoaded('externalIssues')) {
            $externalIssues = $this->externalIssues;
            // Apply exclusion filter to pre-loaded relations if needed
            if ($excludeType === 'extern' && $excludeIssueId) {
                $externalIssues = $externalIssues->filter(function ($issue) use ($excludeIssueId) {
                    return $issue->id !== $excludeIssueId;
                });
            }
        } else {
            $externalIssues = $this->externalIssues()
                ->where('issue_date', '<=', $endDay)
                ->reservedOnOrAfter($startDay)
                ->when($excludeType === 'extern' && $excludeIssueId, function ($q) use ($excludeIssueId): void {
                    $q->where('external_issues.id', '!=', $excludeIssueId);
                })
                ->get();
        }

        [$windowStart, $windowEnd] = self::availabilityWindow($startDate, $endDate);

        $usedQuantity = self::calculatePeakConcurrentUsage(
            collect($internalIssues),
            collect($externalIssues),
            $windowStart,
            $windowEnd
        );

        $total = $this->readyQuantity();
        $available = max($total - $usedQuantity, 0);

        return [
            'available' => $available,
            'total'     => $total,
            'reserved'  => $usedQuantity,
            'quantity'  => $total,
        ];
    }

    /**
     * Abfragefenster [Start, Ende) als Timestamps. Reines Datum (bis 10 Zeichen) und „23:59:59“
     * reichen bis Tagesende einschließlich. Mit Uhrzeit endet das Fenster exklusiv zur angegebenen
     * Zeit – „bis 14:00“ überschneidet sich nicht mit einer Ausgabe ab 14:00. Vorher kam hier
     * +1 s hinzu, mit dem :59 des Batch-Endpunkts wurde daraus 14:01 und direkt anschließende
     * Ausgaben galten als überlappend.
     *
     * @return array{0: int, 1: int}
     */
    public static function availabilityWindow(string $startDate, string $endDate): array
    {
        $end = Carbon::parse($endDate);
        $untilEndOfDay = strlen(trim($endDate)) <= 10 || $end->format('H:i:s') === '23:59:59';

        return [
            Carbon::parse($startDate)->timestamp,
            $untilEndOfDay ? $end->copy()->endOfDay()->timestamp + 1 : $end->timestamp,
        ];
    }

    /**
     * Sweep-line algorithm to calculate peak concurrent usage across all issues.
     * Instead of summing all quantities (which overcounts non-overlapping issues),
     * this finds the maximum quantity in use at any single point in time.
     *
     * @param Collection $internalIssues Issues with start_date, start_time, end_date, end_time
     * @param Collection $externalIssues Issues with issue_date, return_date
     * @return int Peak concurrent usage
     */
    public static function calculatePeakConcurrentUsage(
        Collection $internalIssues,
        Collection $externalIssues,
        ?int $windowStart = null,
        ?int $windowEnd = null
    ): int {
        return self::peakUsageOfIntervals(
            self::usageIntervals($internalIssues, $externalIssues),
            $windowStart,
            $windowEnd
        );
    }

    /**
     * Belegungsintervalle der Ausgaben (Pivot-Menge dieses Artikels). Einmal berechnet, lassen
     * sie sich für viele Zeitfenster wiederverwenden (Überbuchungsprüfung je Ausgabe).
     *
     * @param Collection $internalIssues Issues with start_date, start_time, end_date, end_time
     * @param Collection $externalIssues Issues with issue_date, return_date
     * @return list<array{0: int, 1: int, 2: int}> [Start, Ende exklusiv, Menge]
     */
    public static function usageIntervals(Collection $internalIssues, Collection $externalIssues): array
    {
        $intervals = [];

        foreach ($internalIssues as $issue) {
            $qty = (int) ($issue->pivot->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $startDateStr = Carbon::parse($issue->start_date)->format('Y-m-d');
            $startTime = $issue->start_time ?? '00:00:00';
            $endDateStr = Carbon::parse($issue->end_date ?? $issue->start_date)->format('Y-m-d');
            $endTime = $issue->end_time ?? '23:59:59';

            $start = Carbon::parse("{$startDateStr} {$startTime}")->timestamp;
            // Ende exklusiv: endet eine Ausgabe genau, wenn die nächste beginnt, überlappen sie nicht
            // (bei Gleichstand werden Abgänge vor Zugängen verarbeitet). Das frühere +1 Sekunde
            // machte genau diese Fälle zu Überschneidungen.
            $end = Carbon::parse("{$endDateStr} {$endTime}")->timestamp;

            $intervals[] = [$start, $end, $qty];
        }

        foreach ($externalIssues as $issue) {
            $qty = (int) ($issue->pivot->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $issueDateStr = Carbon::parse($issue->issue_date)->format('Y-m-d');
            // Überfälliges, nicht zurückgegebenes Material bleibt reserviert
            $effectiveReturnDate = $issue instanceof ExternalIssue ? $issue->effectiveReturnDate() : null;
            $returnDateStr = ($effectiveReturnDate ?? Carbon::parse($issue->return_date ?? $issue->issue_date))
                ->format('Y-m-d');

            $start = Carbon::parse("{$issueDateStr} 00:00:00")->timestamp;
            $end = Carbon::parse("{$returnDateStr} 23:59:59")->timestamp + 1; // ganzer Rückgabetag

            $intervals[] = [$start, $end, $qty];
        }

        return $intervals;
    }

    /**
     * Höchste gleichzeitige Nutzung der Intervalle innerhalb des Fensters (Sweep-Line).
     *
     * @param list<array{0: int, 1: int, 2: int}> $intervals [Start, Ende exklusiv, Menge]
     */
    public static function peakUsageOfIntervals(array $intervals, ?int $windowStart = null, ?int $windowEnd = null): int
    {
        $events = [];
        foreach ($intervals as [$start, $end, $quantity]) {
            self::addClippedUsage($events, $start, $end, $quantity, $windowStart, $windowEnd);
        }

        if (empty($events)) {
            return 0;
        }

        // Sort by timestamp; on tie, process removals (-qty) before additions (+qty)
        usort($events, function ($a, $b) {
            if ($a[0] !== $b[0]) {
                return $a[0] <=> $b[0];
            }
            return $a[1] <=> $b[1];
        });

        $current = 0;
        $peak = 0;

        foreach ($events as [$timestamp, $delta]) {
            $current += $delta;
            $peak = max($peak, $current);
        }

        return $peak;
    }

    /**
     * Nutzung nur innerhalb des angefragten Zeitfensters zählen: Überschneidungen zweier Ausgaben
     * VOR dem Fenster (nur eine reicht hinein) machten den Artikel sonst fälschlich überbucht.
     *
     * @param array<int, array{0: int, 1: int}> $events
     */
    private static function addClippedUsage(
        array &$events,
        int $start,
        int $end,
        int $quantity,
        ?int $windowStart,
        ?int $windowEnd
    ): void {
        if ($windowStart !== null) {
            $start = max($start, $windowStart);
        }
        if ($windowEnd !== null) {
            $end = min($end, $windowEnd);
        }
        if ($end <= $start) {
            return;
        }

        $events[] = [$start, $quantity];  // issue starts: add quantity
        $events[] = [$end, -$quantity];   // issue ends: remove quantity
    }

    /**
     * @return BelongsToMany<InventoryTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryTag::class,
            'inventory_article_inventory_tag',
            'inventory_article_id',
            'inventory_tag_id'
        )->withTimestamps();
    }

    /**
     * Einsatzbereite Menge: Menge im Standard-Status (default-Flag) – bei Einzelinventar die
     * Summe der Einzelartikel in diesem Status. Artikel ohne jede gepflegte Statusmenge zählen
     * mit ihrer Gesamtmenge (ältere Artikel aus der Zeit vor den Status). Einzige Quelle für
     * Verfügbarkeit, Planung, Projekt-Tab und Überbuchungsprüfung.
     */
    public function readyQuantity(): float
    {
        $readyStatusId = InventoryArticleStatus::defaultStatusId();

        if ($this->is_detailed_quantity) {
            if (!$this->relationLoaded('detailedArticleQuantities')) {
                $this->load('detailedArticleQuantities');
            }

            return (float) $this->detailedArticleQuantities
                ->filter(fn ($detailed): bool => $readyStatusId !== null
                    && (int) $detailed->inventory_article_status_id === $readyStatusId)
                ->sum(fn ($detailed): float => (float) $detailed->quantity);
        }

        if (!$this->relationLoaded('statusValues')) {
            $this->load('statusValues');
        }

        if ($this->statusValues->isEmpty()) {
            return (float) $this->quantity;
        }

        $readyStatus = $readyStatusId === null ? null : $this->statusValues->firstWhere('id', $readyStatusId);

        return $readyStatus !== null ? (float) $readyStatus->pivot->value : 0.0;
    }
}
