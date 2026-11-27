<?php

namespace Artwork\Modules\Project\Services\Plans;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logo für Veranstaltungsplan und Künstlerplan: Standard ist das große Logo aus den
 * Tool-Einstellungen, für einen einzelnen Export kann eine andere Bilddatei mitgegeben werden.
 * Word kennt kein WebP, solche Bilder werden für den Export in PNG umgewandelt.
 *
 * @phpstan-type PlanLogo array{path: string, temporary: bool}
 */
class ProjectPlanLogoService
{
    /** Bildtypen, die PhpWord unverändert einbetten kann */
    private const EMBEDDABLE_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

    /**
     * Adresse des Standard-Logos für die Vorschau im Export-Dialog; null, wenn keines hinterlegt ist
     * oder die Datei sich nicht in ein Word-Dokument einbetten lässt.
     */
    public function defaultLogoUrl(): ?string
    {
        $path = $this->defaultLogoPath();

        return $path !== null && $this->isUsable($path)
            ? Storage::disk('public')->url(app(GeneralSettings::class)->big_logo_path)
            : null;
    }

    /**
     * Bilddatei für das Dokument: die hochgeladene Datei, sonst das Logo aus den Tool-Einstellungen.
     * Temporäre Dateien (Umwandlung) räumt der Aufrufer mit cleanUp() wieder ab.
     *
     * @return PlanLogo|null
     */
    public function resolve(?UploadedFile $upload): ?array
    {
        $source = $upload?->isValid() ? $upload->getRealPath() : $this->defaultLogoPath();
        if (!is_string($source) || $source === '') {
            return null;
        }

        $imageType = $this->imageType($source);
        if (in_array($imageType, self::EMBEDDABLE_TYPES, true)) {
            return ['path' => $source, 'temporary' => false];
        }

        if ($imageType === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            return $this->convertWebpToPng($source);
        }

        return null;
    }

    /**
     * @param PlanLogo|null $logo
     */
    public function cleanUp(?array $logo): void
    {
        if ($logo !== null && $logo['temporary'] && is_file($logo['path'])) {
            @unlink($logo['path']);
        }
    }

    private function defaultLogoPath(): ?string
    {
        $relativePath = trim((string) app(GeneralSettings::class)->big_logo_path);
        if ($relativePath === '') {
            return null;
        }

        $storage = Storage::disk('public');

        return $storage->exists($relativePath) ? $storage->path($relativePath) : null;
    }

    private function isUsable(string $path): bool
    {
        $imageType = $this->imageType($path);

        return in_array($imageType, self::EMBEDDABLE_TYPES, true)
            || ($imageType === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp'));
    }

    private function imageType(string $path): ?int
    {
        $size = @getimagesize($path);

        return is_array($size) ? (int) $size[2] : null;
    }

    /**
     * @return PlanLogo|null
     */
    private function convertWebpToPng(string $source): ?array
    {
        $image = @imagecreatefromwebp($source);
        if ($image === false) {
            return null;
        }

        imagesavealpha($image, true);
        $target = sys_get_temp_dir() . '/plan-logo-' . Str::uuid()->toString() . '.png';
        $written = imagepng($image, $target);
        imagedestroy($image);

        return $written ? ['path' => $target, 'temporary' => true] : null;
    }
}
