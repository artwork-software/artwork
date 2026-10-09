<?php

namespace Artwork\Modules\ExternalIssue\Models;

use Artwork\Modules\InternalIssue\Models\SpecialItem;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $material_value
 * @property int $issued_by_id
 * @property int $received_by_id
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $return_date
 * @property string $return_remarks
 * @property string $external_name
 * @property string $external_address
 * @property string $external_email
 * @property string $external_phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $counts_as_returned
 */
class ExternalIssue extends Model
{
    use HasFactory;

    public const RETURN_STATUS_RETURNED = 'returned';
    public const RETURN_STATUS_NOT_RETURNED = 'not_returned';

    /**
     * Frühester Start der Rückgabe-Erfassung (Status + Erinnerung, Release 13.08.2026). Der tatsächliche
     * Stichtag je Installation ist returnTrackingSince() – Häuser haben das Release später eingespielt.
     */
    public const RETURN_TRACKING_SINCE = '2026-08-13';

    private const RETURN_TRACKING_SINCE_BINDING = 'artwork.external_issue.return_tracking_since';

    protected $fillable = [
        'material_value', 'issued_by_id', 'received_by_id', 'project_id',
        'issue_date', 'return_date', 'return_remarks', 'return_status', 'return_notification_sent_at',
        'external_name', 'external_address', 'external_email', 'external_phone', 'special_items_done',
        'name'
    ];

    protected $casts = [
        'material_value' => 'decimal:2',
        'issued_by_id' => 'integer',
        'received_by_id' => 'integer',
        'project_id' => 'integer',
        'special_items_done' => 'boolean',
        'issue_date' => 'date:Y-m-d',
        'return_date' => 'date:Y-m-d',
        'return_notification_sent_at' => 'datetime',
    ];

    protected $appends = [
        'issue_date_formatted',
        'return_date_formatted',
        'counts_as_returned',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id', 'id', 'user');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id', 'id', 'user');
    }

    /**
     * @return BelongsTo<\Artwork\Modules\Project\Models\Project, $this>
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Artwork\Modules\Project\Models\Project::class, 'project_id');
    }

    /**
     * @return HasMany<ExternalIssueFile, $this>
     */
    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExternalIssueFile::class, 'external_issue_id', 'id');
    }

    /**
     * @return MorphToMany<InventoryArticle, $this>
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            InventoryArticle::class,
            'issuable',
            'issuable_inventory_article'
        )
            // Artikel im Papierkorb bleiben Teil der Ausgabe (Entscheidung 05.10.2026); vorher
            // verschwanden sie aus der Anzeige und das nächste Speichern löste die Verknüpfung.
            ->withTrashed()
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * @return MorphMany<SpecialItem, $this>
     */
    public function specialItems(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(SpecialItem::class, 'issuable');
    }

    public function getIssueDateFormattedAttribute(): string
    {
        return Carbon::parse($this->issue_date)->translatedFormat('d. F Y');
    }

    public function getReturnDateFormattedAttribute(): string
    {
        if ($this->return_date === null) {
            return '';
        }
        return Carbon::parse($this->return_date)->translatedFormat('d. F Y');
    }

    public function scopeOverlapping($query, ?string $from, ?string $to)
    {
        return $query
            // beide Grenzen gesetzt
            ->when($from && $to, function ($q) use ($from, $to): void {
                $q->where(function ($qq) use ($from, $to): void {
                    $qq->whereDate('issue_date', '<=', $to)
                        ->where(function ($qqq) use ($from): void {
                            $qqq->whereNull('return_date')
                                ->orWhereDate('return_date', '>=', $from);
                        });
                });
            })
            // nur FROM: alles, was am/vor unendlicher Zukunft läuft & nicht vor FROM endet
            ->when($from && !$to, function ($q) use ($from): void {
                $q->where(function ($qq) use ($from): void {
                    $qq->whereNull('return_date')
                        ->orWhereDate('return_date', '>=', $from);
                });
            })
            // nur TO: alles, was bis TO begonnen hat
            ->when(!$from && $to, function ($q) use ($to): void {
                $q->whereDate('issue_date', '<=', $to);
            });
    }

    /**
     * Ende der Reservierung für Verfügbarkeit und Planung. Nicht zurückgegebenes, überfälliges
     * Material bleibt bis heute reserviert (Entscheidung 05.10.2026) – vorher galt es ab dem
     * geplanten Rückgabedatum als frei, obwohl es noch draußen war.
     */
    public function effectiveReturnDate(): ?\Carbon\Carbon
    {
        $rawReturnDate = $this->getRawOriginal('return_date') ?? $this->getAttribute('return_date');
        if ($rawReturnDate === null) {
            return null;
        }

        $returnDate = \Carbon\Carbon::parse($rawReturnDate)->startOfDay();
        $today = \Carbon\Carbon::today();
        if (!$this->isReturned() && $returnDate->lt($today)) {
            return $today;
        }

        return $returnDate;
    }

    /**
     * Zurückgegeben über den Rückgabe-Status oder – Altbestand vor dem Status (08/2026) und das
     * Feld „Erhalten von“ im Formular – über eine eingetragene Rücknahme (wie die Rückgabe-Erinnerung).
     */
    public function isReturned(): bool
    {
        return $this->return_status === self::RETURN_STATUS_RETURNED ||
            $this->received_by_id !== null ||
            $this->isUntrackedLegacyIssue();
    }

    /**
     * Für Listen/Badges: dieselbe Regel wie Verfügbarkeit und Überfällig-Filter.
     */
    public function getCountsAsReturnedAttribute(): bool
    {
        return $this->isReturned();
    }

    /**
     * Stichtag der Rückgabe-Erfassung in dieser Installation – beim Deploy einmalig festgeschrieben
     * (Settings-Migration 2026_10_06_160000: Datum des Backfill-Stempels vom Einspielen der Erfassung,
     * frühestens RETURN_TRACKING_SINCE). Ohne den Eintrag (noch nicht migriert) gilt die Konstante.
     * Altbestand – vorher angelegt UND vorher fällig, ohne Status, ohne „Erhalten von“ und ohne spätere
     * Erinnerung – gilt als zurückgegeben; sonst blieben Jahre an Altbestand bis heute reserviert.
     */
    public static function returnTrackingSince(): string
    {
        // nur einen gefundenen Wert merken (im Container: je Request bzw. Worker-Prozess, je Test neu) –
        // Worker, die vor dem migrate starten, lesen ihn so später nach
        if (app()->bound(self::RETURN_TRACKING_SINCE_BINDING)) {
            return app(self::RETURN_TRACKING_SINCE_BINDING);
        }

        $payload = \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'inventory')
            ->where('name', 'return_tracking_since')
            ->value('payload');
        $since = is_string($payload) ? json_decode($payload, true) : null;
        if (!is_string($since) || $since === '') {
            return self::RETURN_TRACKING_SINCE;
        }

        app()->instance(self::RETURN_TRACKING_SINCE_BINDING, $since);

        return $since;
    }

    /**
     * Für Tests nach einem Wechsel des festgeschriebenen Stichtags.
     */
    public static function forgetReturnTrackingSince(): void
    {
        app()->forgetInstance(self::RETURN_TRACKING_SINCE_BINDING);
    }

    /**
     * Vor der Rückgabe-Erfassung angelegt und fällig, ohne Status und ohne Erinnerung nach dem Stichtag.
     * Fehlendes created_at zählt als Altbestand – wie in scopeNotReturned (NULL >= … ist falsch).
     */
    private function isUntrackedLegacyIssue(): bool
    {
        if ($this->return_status !== null) {
            return false;
        }

        $rawReturnDate = $this->getRawOriginal('return_date') ?? $this->getAttribute('return_date');
        $rawCreatedAt = $this->getRawOriginal('created_at') ?? $this->getAttribute('created_at');
        $rawReminderSentAt = $this->getRawOriginal('return_notification_sent_at')
            ?? $this->getAttribute('return_notification_sent_at');
        $since = self::returnTrackingSince();

        // eine Erinnerung nach dem Stichtag heißt: die Ausgabe wird schon erfasst
        if ($rawReminderSentAt !== null && \Carbon\Carbon::parse($rawReminderSentAt)->toDateString() > $since) {
            return false;
        }

        return $rawReturnDate !== null &&
            \Carbon\Carbon::parse($rawReturnDate)->toDateString() < $since &&
            ($rawCreatedAt === null ||
                \Carbon\Carbon::parse($rawCreatedAt)->toDateString() < $since);
    }

    /**
     * Nicht zurückgegeben – SQL-Gegenstück zu !isReturned().
     */
    public function scopeNotReturned(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('external_issues.received_by_id')
            ->where(function (\Illuminate\Database\Eloquent\Builder $notReturned): void {
                $notReturned->where('external_issues.return_status', '!=', self::RETURN_STATUS_RETURNED)
                    ->orWhere(function (\Illuminate\Database\Eloquent\Builder $tracked): void {
                        $tracked->whereNull('external_issues.return_status')
                            ->where(function (\Illuminate\Database\Eloquent\Builder $afterTrackingStart): void {
                                $since = self::returnTrackingSince();
                                $afterTrackingStart
                                    ->where('external_issues.return_date', '>=', $since)
                                    ->orWhere('external_issues.created_at', '>=', $since)
                                    ->orWhereDate('external_issues.return_notification_sent_at', '>', $since);
                            });
                    });
            });
    }

    /**
     * Ausgaben, deren Reservierung (effectiveReturnDate) am Datum noch läuft oder danach endet.
     */
    public function scopeReservedOnOrAfter(\Illuminate\Database\Eloquent\Builder $query, string $date): void
    {
        $query->where(function (\Illuminate\Database\Eloquent\Builder $reserved) use ($date): void {
            $reserved->where('external_issues.return_date', '>=', $date)
                ->orWhereNull('external_issues.return_date');

            if (\Carbon\Carbon::parse($date)->startOfDay()->lte(\Carbon\Carbon::today())) {
                $reserved->orWhere(function (\Illuminate\Database\Eloquent\Builder $overdue): void {
                    $this->scopeNotReturned($overdue);
                });
            }
        });
    }
}
