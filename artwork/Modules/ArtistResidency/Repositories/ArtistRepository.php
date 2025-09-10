<?php

namespace Artwork\Modules\ArtistResidency\Repositories;

use Artwork\Modules\ArtistResidency\Models\Artist;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ArtistRepository
{
    public function findById(int $id, bool $lockForUpdate = false): ?Artist
    {
        $q = Artist::query()->whereKey($id);
        if ($lockForUpdate) $q->lockForUpdate();
        return $q->first();
    }

    public function normalizeName(string $name): string
    {
        $trimmed  = trim(preg_replace('/\s+/u', ' ', (string)$name));
        return mb_strtolower($trimmed, 'UTF-8');
    }

    public function findByNormalizedName(string $name, bool $lockForUpdate = false): ?Artist
    {
        $norm = $this->normalizeName($name); // squish + lower in PHP
        $q = Artist::query()->whereRaw('LOWER(TRIM(name)) = ?', [$norm]);
        if ($lockForUpdate) {
            $q->lockForUpdate();
        }
        return $q->first();
    }

    public function create(array $data): Artist
    {
        return Artist::create($data);
    }

    public function update(Artist $artist, array $data): Artist
    {
        // nur nicht-leere Werte anwenden
        $clean = collect($data)->filter(fn($v) => !is_null($v) && $v !== '')->all();
        if (!empty($clean)) {
            $artist->fill($clean)->save();
        }
        return $artist->refresh();
    }

    public function getOrCreateByNameForUpdate(string $name, array $extra = []): Artist
    {
        return DB::transaction(function () use ($name, $extra) {
            // 1) versuchen zu finden (gesperrt)
            $found = $this->findByNormalizedName($name, lockForUpdate: true);
            if ($found) {
                // vorsichtig updaten (nur nicht-leere Felder)
                return $this->update($found, $extra);
            }

            // 2) anlegen – Unique-Constraint abfangen (falls parallel jemand angelegt hat)
            try {
                return $this->create(array_merge(['name' => trim($name)], $extra));
            } catch (QueryException $e) {
                // Bei Unique-Verletzung erneut lesen
                $retry = $this->findByNormalizedName($name, lockForUpdate: true);
                if ($retry) {
                    return $this->update($retry, $extra);
                }
                throw $e;
            }
        });
    }
}
