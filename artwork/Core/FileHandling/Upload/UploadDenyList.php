<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

/**
 * Zentrale Denylist für Uploads: alles, was ein Webserver an einen Interpreter reichen oder ein Browser
 * auf der App-Origin als Markup/Skript rendern könnte. Geprüft werden Client-Endung und per finfo
 * erkannter Inhalt; Treffer werden an jedem Upload-Pfad abgelehnt (HandlesFileUpload, StoredFileName, SafeUploadFile).
 */
final class UploadDenyList
{
    /**
     * @var list<string>
     */
    public const EXTENSIONS = [
        // Server-seitig ausführbar
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'pht', 'phar',
        'shtml', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'jsp', 'jspx', 'asp', 'aspx',
        'exe', 'bat', 'cmd', 'com',
        // Webserver-Konfiguration
        'htaccess', 'htpasswd',
        // Vom Browser auf der App-Origin renderbar (Markup / Skript-fähiger Inhalt)
        'html', 'htm', 'xhtml', 'xht', 'svg', 'svgz', 'xml', 'xsl', 'xslt',
    ];

    /**
     * @var list<string>
     */
    public const MIME_TYPES = [
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'application/xslt+xml',
        'text/php',
        'text/x-php',
        'application/php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'application/x-phar',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'application/x-sh',
        'application/x-shellscript',
    ];

    /**
     * Sniffer-Ergebnisse ohne Aussagekraft; ihre registrierten Endungen werden nicht gegen die Liste gehalten.
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
     * Beanstandetes Merkmal (Endung oder MIME-Typ) oder null.
     */
    public static function deniedReason(UploadedFile $file): ?string
    {
        $clientExtension = self::normaliseExtension($file->getClientOriginalExtension());
        if (self::deniesExtension($clientExtension)) {
            return $clientExtension;
        }

        $mimeType = self::detectMimeType($file);
        if ($mimeType === null) {
            return null;
        }

        if (self::deniesMimeType($mimeType)) {
            return $mimeType;
        }

        if (in_array($mimeType, self::GENERIC_MIME_TYPES, true)) {
            return null;
        }

        foreach (MimeTypes::getDefault()->getExtensions($mimeType) as $extension) {
            if (self::deniesExtension(self::normaliseExtension($extension))) {
                return $mimeType;
            }
        }

        return null;
    }

    public static function denies(UploadedFile $file): bool
    {
        return self::deniedReason($file) !== null;
    }

    public static function deniesExtension(string $extension): bool
    {
        return in_array(self::normaliseExtension($extension), self::EXTENSIONS, true);
    }

    public static function deniesMimeType(string $mimeType): bool
    {
        return in_array(strtolower(trim($mimeType)), self::MIME_TYPES, true);
    }

    /**
     * Kleinbuchstaben, nur [a-z0-9] - "PhP", "php " oder "php\0" werden alle zu "php".
     */
    public static function normaliseExtension(string $extension): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?? '';
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
}
