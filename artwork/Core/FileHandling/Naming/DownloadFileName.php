<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Naming;

/**
 * Guards the name a generated file is offered under.
 *
 * Files exported to disk are stored under a hash, so the readable name travels
 * separately (query parameter) and therefore arrives as untrusted input.
 * Symfony's HeaderUtils::makeDisposition() throws on "%", "/" and "\", which
 * would turn a tampered link into a 500 - so anything unsuitable is dropped
 * here and the caller falls back to the file's own name.
 */
final class DownloadFileName
{
    private const MAX_LENGTH = 200;

    private function __construct()
    {
    }

    public static function sanitize(mixed $name): ?string
    {
        if (!is_string($name)) {
            return null;
        }

        // Path separators, percent signs and control characters (including any
        // header terminator) are removed rather than escaped.
        $name = preg_replace('/[\/\\\\%\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }

        return mb_substr($name, 0, self::MAX_LENGTH);
    }
}
