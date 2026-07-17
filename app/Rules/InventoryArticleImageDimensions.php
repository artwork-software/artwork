<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Throwable;

class InventoryArticleImageDimensions implements ValidationRule
{
    private const int MAX_DIMENSION = 8192;

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
            $dimensions = @getimagesize($value->getRealPath());

            if ($dimensions === false && class_exists(\Imagick::class)) {
                $image = new \Imagick();
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
        }
    }
}
