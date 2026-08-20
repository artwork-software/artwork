<?php

namespace Artwork\Modules\User\Models\Traits;

use Artwork\Core\FileHandling\Naming\StoredFileName;
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

        return $this->letterAvatarUrl($this->initials());
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

    /**
     * Buchstaben-Avatar als URL statt als base64-Data-URI: Die Grafik ist fuer
     * jedes Initialen-Paar identisch, als Data-URI landete sie aber in JEDEM
     * Termin des Kalender-Payloads erneut (~686 Byte, ein Drittel des Monats-
     * pakets). Als URL wiegt sie ein paar Byte und der Browser holt das Bild
     * genau einmal aus dem Cache.
     */
    protected function letterAvatarUrl(string $letters): string
    {
        return route('generate-avatar-image', [
            'letters' => $letters !== '' ? $letters : 'U',
            'bg' => (string) config('artwork.avatar.bg', '#4F46E5'),
            'color' => (string) config('artwork.avatar.fg', '#FFFFFF'),
        ]);
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
                'profile_photo_path' => $photo->storeAs(
                    $storagePath,
                    StoredFileName::forUpload($photo),
                    ['disk' => $this->profilePhotoDisk()]
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

        $firstName = $this->first_name ?? '';
        $lastName = $this->last_name ?? '';
        $firstLetter = !empty($firstName) ? mb_substr($firstName, 0, 1) : '';
        $lastLetter = !empty($lastName) ? mb_substr($lastName, 0, 1) : '';
        $letters = $firstLetter . $lastLetter;

        return  route('generate-avatar-image', ['letters' => $letters ?: 'U']);
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
