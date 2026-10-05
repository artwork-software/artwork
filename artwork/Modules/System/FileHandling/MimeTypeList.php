<?php

namespace Artwork\Modules\System\FileHandling;

/**
 * Auswahlliste für die Dateityp-Einstellungen. Skript-/markup-fähige Typen (html, svg, xml, php, …) fehlen:
 * sie wären auf der public-Disk als Stored XSS ausnutzbar und werden in HandlesFileUpload zusätzlich abgelehnt.
 */
class MimeTypeList
{
    public const MIME_TYPES = [
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'wmv' => 'video/x-ms-wmv',
        'mp4' => 'video/mp4',
        'm4v' => 'video/x-m4v',
        'webm' => 'video/webm',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',

        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    public const IMAGE_MIME_TYPES = [
        '*' => '*',
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
    ];
}
