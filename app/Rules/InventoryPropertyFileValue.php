<?php

namespace App\Rules;

use Artwork\Modules\Inventory\Enums\InventoryPropertyTypeEnum;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Werte von Properties des Typs "file" sind Pfade auf der Storage-Disk; beim endgültigen Löschen
 * eines Artikels werden Datei und Ordner entfernt. Nur genau eine Datei in einem Upload-Ordner
 * unterhalb von {@see self::BASE_DIR} ist erlaubt – sonst löscht "uploads/inventory-properties/../.."
 * die komplette Disk.
 */
class InventoryPropertyFileValue implements ValidationRule, DataAwareRule
{
    public const string BASE_DIR = 'uploads/inventory-properties';

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param int|null $propertyId Property-ID; ohne Angabe wird das Geschwisterfeld "id" gelesen
     *                             (z. B. "properties.3.value" → "properties.3.id").
     */
    public function __construct(private readonly ?int $propertyId = null)
    {
    }

    public static function isSafePath(string $path): bool
    {
        return preg_match('#^' . preg_quote(self::BASE_DIR, '#') . '/[A-Za-z0-9-]+/[^/\\\\]+$#', $path) === 1
            && !str_contains($path, '..')
            && !str_contains($path, "\0")
            && basename($path) !== '.';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $propertyId = $this->propertyId ?? Arr::get($this->data, Str::beforeLast($attribute, '.') . '.id');

        if (!is_numeric($propertyId)) {
            return;
        }

        $isFileProperty = InventoryArticleProperties::query()
            ->whereKey((int) $propertyId)
            ->where('type', InventoryPropertyTypeEnum::FILE->value)
            ->exists();

        if ($isFileProperty && (!is_string($value) || !self::isSafePath($value))) {
            $fail('validation.in')->translate();
        }
    }
}
