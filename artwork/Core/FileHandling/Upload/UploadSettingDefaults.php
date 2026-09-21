<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

/**
 * Standardwerte der Upload-Einstellungen (Gruppe "general"): Endungs-Allowlists je Dateibereich
 * und Größenlimits in MB. Seeder und Migrationen lesen ausschließlich von hier.
 */
final class UploadSettingDefaults
{
    public const SETTINGS_GROUP = 'general';

    /**
     * @var list<string>
     */
    public const DOCUMENT_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
    ];

    /**
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    public const FILE_SIZE_MB = 150;

    private function __construct()
    {
    }

    public static function mimeTypesKey(ArtworkFileTypes $type): string
    {
        return sprintf('allowed_%s_file_mimetypes', $type->value);
    }

    public static function fileSizeKey(ArtworkFileTypes $type): string
    {
        return sprintf('allowed_%s_file_size', $type->value);
    }

    /**
     * @return list<string>
     */
    public static function mimeTypesFor(ArtworkFileTypes $type): array
    {
        return match ($type) {
            ArtworkFileTypes::BRANDING => self::IMAGE_EXTENSIONS,
            ArtworkFileTypes::PROJECT,
            ArtworkFileTypes::ROOM,
            ArtworkFileTypes::CONTRACT => self::DOCUMENT_EXTENSIONS,
        };
    }

    /**
     * Setting-Name => Endungsliste, für alle Dateibereiche.
     *
     * @return array<string, list<string>>
     */
    public static function mimeTypes(): array
    {
        $defaults = [];
        foreach (ArtworkFileTypes::cases() as $type) {
            $defaults[self::mimeTypesKey($type)] = self::mimeTypesFor($type);
        }

        return $defaults;
    }

    /**
     * Setting-Name => Größenlimit in MB, für alle Dateibereiche.
     *
     * @return array<string, int>
     */
    public static function fileSizes(): array
    {
        $defaults = [];
        foreach (ArtworkFileTypes::cases() as $type) {
            $defaults[self::fileSizeKey($type)] = self::FILE_SIZE_MB;
        }

        return $defaults;
    }

    /**
     * Alle Upload-Settings (Allowlists und Größenlimits) als Setting-Name => Wert.
     *
     * @return array<string, list<string>|int>
     */
    public static function all(): array
    {
        return self::mimeTypes() + self::fileSizes();
    }
}
