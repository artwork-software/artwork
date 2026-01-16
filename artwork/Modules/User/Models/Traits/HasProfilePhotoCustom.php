<?php

namespace Artwork\Modules\User\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

trait HasProfilePhotoCustom
{

    public function getProfilePhotoUrlAttribute(): string
    {
        if (!empty($this->profile_photo_path)) {
            return asset('storage/' . ltrim($this->profile_photo_path, '/'));
        }

        $letters = $this->initials();
        $bg = (string) config('artwork.avatar.bg', '#4F46E5'); // eine Farbe
        $fg = (string) config('artwork.avatar.fg', '#FFFFFF'); // Textfarbe fix

        $svg = $this->makeAvatarSvg($letters, $bg, $fg);

        return $this->svgToDataUri($svg);
    }

    private function initials(): string
    {
        $first = trim((string) ($this->first_name ?? ''));
        $last  = trim((string) ($this->last_name ?? ''));

        $a = $first !== '' ? Str::upper(Str::substr($first, 0, 1)) : '';
        $b = $last  !== '' ? Str::upper(Str::substr($last, 0, 1)) : '';

        $letters = $a . $b;

        if ($letters === '') {
            $fallback = trim((string) ($this->work_name ?? $this->email ?? 'U'));

            // wenn email: nimm Teil vor @
            if (str_contains($fallback, '@')) {
                $fallback = Str::before($fallback, '@');
            }

            $letters = Str::upper(Str::substr($fallback, 0, 2));
        }

        return $letters;
    }

    private function makeAvatarSvg(string $letters, string $bgColor, string $textColor): string
    {
        // echtes Zentrieren: x/y in px + dominant-baseline="central"
        // Font-weight bewusst nicht zu fett (passt zu deinem Stil)
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" role="img" aria-label="Avatar {$letters}">
  <rect x="0" y="0" width="64" height="64" rx="12" ry="12" fill="{$bgColor}"/>
  <text x="32" y="32"
        text-anchor="middle"
        dominant-baseline="central"
        font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Inter,Arial,sans-serif"
        font-size="26"
        font-weight="500"
        letter-spacing="0.5"
        fill="{$textColor}">{$letters}</text>
</svg>
SVG;
    }

    private function svgToDataUri(string $svg): string
    {
        // base64 ist am stabilsten (keine Probleme mit Sonderzeichen/Quotes)
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    /**
     * Update the user's profile photo.
     *
     * @param  \Illuminate\Http\UploadedFile  $photo
     * @param  string  $storagePath
     * @return void
     */
    public function updateProfilePhoto(UploadedFile $photo, $storagePath = 'profile-photos')
    {
        tap($this->profile_photo_path, function ($previous) use ($photo, $storagePath) {
            $this->forceFill([
                'profile_photo_path' => $photo->storePublicly(
                    $storagePath, ['disk' => $this->profilePhotoDisk()]
                ),
            ])->save();

            if ($previous) {
                Storage::disk($this->profilePhotoDisk())->delete($previous);
            }
        });
    }

    /**
     * Delete the user's profile photo.
     *
     * @return void
     */
    public function deleteProfilePhoto(): void
    {
        if (! Features::managesProfilePhotos()) {
            return;
        }

        if (is_null($this->profile_photo_path)) {
            return;
        }

        Storage::disk($this->profilePhotoDisk())->delete($this->profile_photo_path);

        $this->forceFill([
            'profile_photo_path' => null,
        ])->save();
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->profile_photo_path
                ? asset('storage/' . $this->profile_photo_path)
                : $this->defaultProfilePhotoUrl();
        });
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     *
     * @return string
     */
    protected function defaultProfilePhotoUrl()
    {
        $name = $this->name ?? '';
        $name = trim(collect(explode(' ', $name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));


        return  route('generate-avatar-image', ['letters' => $this->first_name[0] . $this->last_name[0]]);
    }

    /**
     * Get the disk that profile photos should be stored on.
     *
     * @return string
     */
    protected function profilePhotoDisk()
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : config('jetstream.profile_photo_disk', 'public');
    }
}
