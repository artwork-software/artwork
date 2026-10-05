<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Naming;

use Artwork\Core\FileHandling\Upload\DeniedUploadFileException;
use Artwork\Core\FileHandling\Upload\UploadDenyList;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Mime\MimeTypes;

/**
 * Builds the name a file is stored under on disk.
 *
 * The result is always "<32 hex chars>[.<extension>]" - no user controlled
 * characters, no date, no time. The human readable name lives in the database
 * (name / original_name / ...) and is handed to the client at download time.
 */
final class StoredFileName
{
    /**
     * Every name this class produces matches this pattern.
     */
    public const PATTERN = '/^[a-f0-9]{32}(\.[a-z0-9]{1,16})?$/';

    private const MAX_EXTENSION_LENGTH = 16;

    /**
     * Mime types the sniffer reports when it cannot tell what a file is; the client extension is used instead.
     *
     * @var list<string>
     */
    private const GENERIC_MIME_TYPES = [
        'application/octet-stream',
        'application/x-empty',
        'inode/x-empty',
    ];

    private function __construct()
    {
    }

    /**
     * @throws DeniedUploadFileException when the file is on the UploadDenyList
     */
    public static function forUpload(UploadedFile $file): string
    {
        if (UploadDenyList::denies($file)) {
            throw DeniedUploadFileException::forFile($file);
        }

        return self::build(
            $file->getClientOriginalName(),
            self::resolveUploadExtension($file)
        );
    }

    /**
     * Name for a file the application generates itself (PDF export, converted
     * image, thumbnail). $seed only adds entropy, it never reaches the result.
     *
     * @throws InvalidArgumentException when the application asks for a denied extension
     */
    public static function forGenerated(string $extension, string $seed = ''): string
    {
        $extension = self::normaliseExtension($extension);

        if (UploadDenyList::deniesExtension($extension)) {
            throw new InvalidArgumentException(sprintf('Generated files must not use the extension "%s".', $extension));
        }

        return self::build($seed, $extension);
    }

    private static function build(string $seed, string $extension): string
    {
        $hash = md5(uniqid('', true) . $seed . Str::random(40));

        return $extension === '' ? $hash : $hash . '.' . $extension;
    }

    /**
     * The extension follows the sniffed content ("x.jpg" containing PNG is stored as ".png"); the client
     * extension only wins when the content is unrecognisable or it is registered for the detected mime type.
     */
    private static function resolveUploadExtension(UploadedFile $file): string
    {
        $clientExtension = self::normaliseExtension($file->getClientOriginalExtension());
        $mimeType = self::detectMimeType($file);

        if ($mimeType === null || in_array($mimeType, self::GENERIC_MIME_TYPES, true)) {
            return $clientExtension;
        }

        $detectedExtensions = array_map(
            static fn (string $extension): string => self::normaliseExtension($extension),
            MimeTypes::getDefault()->getExtensions($mimeType)
        );
        $detectedExtensions = array_values(array_filter($detectedExtensions, static fn (string $e): bool => $e !== ''));

        if ($detectedExtensions === []) {
            return $clientExtension;
        }

        if ($clientExtension !== '' && in_array($clientExtension, $detectedExtensions, true)) {
            return $clientExtension;
        }

        return $detectedExtensions[0];
    }

    private static function detectMimeType(UploadedFile $file): ?string
    {
        try {
            $mimeType = $file->getMimeType();
        } catch (\Throwable) {
            return null;
        }

        return is_string($mimeType) && $mimeType !== '' ? strtolower($mimeType) : null;
    }

    private static function normaliseExtension(string $extension): string
    {
        return substr(UploadDenyList::normaliseExtension($extension), 0, self::MAX_EXTENSION_LENGTH);
    }
}
