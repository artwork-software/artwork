<?php

namespace App\Rules;

use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Throwable;

class InventoryArticleImageDimensions implements ValidationRule
{
    private const int MAX_DIMENSION = 8192;

    // Zusätzlich zur Kantenlänge: Gesamtpixel (8192² wären 67 MP) - Dekodierung im Job ist darauf begrenzt
    private const int MAX_PIXELS = InventoryArticleImageService::MAX_PIXELS;

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return;
        }

        try {
            try {
                $dimensions = getimagesize($value->getRealPath());
            } catch (\Throwable) {
                // ungültiges Bild: getimagesize warnt (Laravel wirft daraus eine Exception) → Imagick-Fallback
                $dimensions = false;
            }

            if ($dimensions === false && class_exists(\Imagick::class)) {
                $image = new \Imagick();
                InventoryArticleImageService::applyImagickResourceLimits($image);
                $image->pingImage($value->getRealPath());
                $dimensions = [$image->getImageWidth(), $image->getImageHeight()];
                $image->clear();
            }
        } catch (Throwable) {
            $dimensions = false;
        }

        if ($dimensions === false) {
            $fail(__('The :attribute must be a readable image.'));

            return;
        }

        if ($dimensions[0] > self::MAX_DIMENSION || $dimensions[1] > self::MAX_DIMENSION) {
            $fail(__('The :attribute dimensions must not exceed :max pixels.', [
                'max' => number_format(self::MAX_DIMENSION, 0, ',', '.'),
            ]));

            return;
        }

        if (((int) $dimensions[0] * (int) $dimensions[1]) > self::MAX_PIXELS) {
            $fail(__('The :attribute must not exceed :max megapixels.', [
                'max' => (int) round(self::MAX_PIXELS / 1_000_000),
            ]));
        }
    }
}
