<?php

namespace Artwork\Modules\Trash\Services;

use Artwork\Modules\Trash\Models\TrashRecord;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Löschinformationen für die Papierkorb-Listen: wann gelöscht, von wem und wie lange der Eintrag
 * noch liegt, bis die tägliche Bereinigung (artwork:purge-trash) ihn endgültig löscht.
 */
class TrashInfoService
{
    /** Standardfrist von artwork:purge-trash */
    public const RETENTION_DAYS = 30;

    /**
     * @param iterable<Model> $models Einträge desselben Modells
     * @return array<int|string, array{deleted_at: string|null, deleted_by: string|null, days_left: int|null}>
     */
    public function forModels(iterable $models): array
    {
        $models = collect($models)->filter(static fn ($model): bool => $model instanceof Model)->values();
        if ($models->isEmpty()) {
            return [];
        }

        $names = TrashRecord::query()
            ->with('user:id,first_name,last_name')
            ->where('subject_type', $models->first()->getMorphClass())
            ->whereIn('subject_id', $models->map->getKey()->all())
            ->get()
            ->mapWithKeys(static fn (TrashRecord $record): array => [
                $record->subject_id => $record->user
                    ? trim($record->user->first_name . ' ' . $record->user->last_name)
                    : null,
            ]);

        $now = Carbon::now();

        return $models->mapWithKeys(fn (Model $model): array => [
            $model->getKey() => $this->describe($model->getAttribute('deleted_at'), $names[$model->getKey()] ?? null, $now),
        ])->all();
    }

    /**
     * @return array{deleted_at: string|null, deleted_by: string|null, days_left: int|null}
     */
    private function describe(mixed $deletedAt, ?string $deletedBy, CarbonInterface $now): array
    {
        $deletedAt = $deletedAt !== null ? Carbon::parse($deletedAt) : null;

        return [
            'deleted_at' => $deletedAt?->format('d.m.Y, H:i'),
            'deleted_by' => $deletedBy !== '' ? $deletedBy : null,
            'days_left' => $deletedAt !== null ? self::daysLeft($deletedAt, $now) : null,
        ];
    }

    /**
     * Volle Tage bis zum Ablauf der Frist, aufgerundet; 0 heißt: bei der nächsten Bereinigung.
     */
    public static function daysLeft(CarbonInterface $deletedAt, CarbonInterface $now): int
    {
        $secondsLeft = $deletedAt->copy()->addDays(self::RETENTION_DAYS)->getTimestamp() - $now->getTimestamp();

        return max(0, (int) ceil($secondsLeft / 86400));
    }
}
