<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Naming;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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
     * Extensions a web server may hand to an interpreter. Files on the "public"
     * disk are reachable under /storage/**, so these are rewritten rather than
     * kept. Defence in depth - most, but not all, upload paths also run a mime
     * allow list via HandlesFileUpload.
     *
     * @var list<string>
     */
    private const DENIED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'pht',
        'phar', 'shtml', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'htaccess',
        'htpasswd', 'jsp', 'jspx', 'asp', 'aspx', 'exe', 'bat', 'cmd', 'com',
    ];

    private const DENIED_REPLACEMENT = 'bin';

    private function __construct()
    {
    }

    public static function forUpload(UploadedFile $file): string
    {
        return self::build(
            $file->getClientOriginalName(),
            self::resolveUploadExtension($file)
        );
    }

    /**
     * Name for a file the application generates itself (PDF export, converted
     * image, thumbnail). $seed only adds entropy, it never reaches the result.
     */
    public static function forGenerated(string $extension, string $seed = ''): string
    {
        return self::build($seed, self::normaliseExtension($extension));
    }

    private static function build(string $seed, string $extension): string
    {
        $hash = md5(uniqid('', true) . $seed . Str::random(40));

        return $extension === '' ? $hash : $hash . '.' . $extension;
    }

    private static function resolveUploadExtension(UploadedFile $file): string
    {
        $extension = self::normaliseExtension($file->getClientOriginalExtension());

        if ($extension === '') {
            // Symfony guesses from the detected mime type, never from the name.
            $extension = self::normaliseExtension((string) $file->extension());
        }

        return $extension;
    }

    private static function normaliseExtension(string $extension): string
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?? '';
        $extension = substr($extension, 0, self::MAX_EXTENSION_LENGTH);

        if ($extension === '') {
            return '';
        }

        return in_array($extension, self::DENIED_EXTENSIONS, true)
            ? self::DENIED_REPLACEMENT
            : $extension;
    }
}
