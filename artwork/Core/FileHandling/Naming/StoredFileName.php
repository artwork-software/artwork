<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Naming;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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
     * Extensions a web server may hand to an interpreter OR serve with a
     * script-capable content type (text/html, image/svg+xml, XML with XSLT).
     * Files on the "public" disk are reachable under /storage/**, so these are
     * rewritten to ".bin" rather than kept - the browser then never renders
     * them on the application origin. Defence in depth - most, but not all,
     * upload paths also run a mime allow list via HandlesFileUpload.
     *
     * @var list<string>
     */
    private const DENIED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'pht',
        'phar', 'shtml', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'htaccess',
        'htpasswd', 'jsp', 'jspx', 'asp', 'aspx', 'exe', 'bat', 'cmd', 'com',
        'html', 'htm', 'xhtml', 'xht', 'svg', 'svgz', 'xml', 'xsl', 'xslt',
    ];

    private const DENIED_REPLACEMENT = 'bin';

    /**
     * Mime types the sniffer reports when it cannot tell what a file is. Their
     * guessed extension carries no information, so the client extension is
     * used instead (after the deny list).
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

    /**
     * The extension is derived from the sniffed content, never from the client
     * name alone: a PNG uploaded as "x.html" is stored as ".png", an HTML file
     * uploaded as "x.png" ends up as ".bin". The client extension only wins
     * when the content is unrecognisable (or generic binary) and it passes the
     * deny list - or when it is one of the extensions registered for the
     * detected mime type ("jpg" stays "jpg" instead of becoming "jpeg").
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
