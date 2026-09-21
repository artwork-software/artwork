<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTML_PDF_BINARY', '/usr/local/bin/wkhtmltopdf'),
        'timeout' => false,
        // Keine PDF-View nutzt JavaScript (Sicherheits-Audit 21.09.2026, F): wkhtmltopdf
        // soll Skripte aus Nutzertext nie ausführen. Lokaler Dateizugriff bleibt aus
        // (Bilder werden als Base64/Data-URI eingebettet).
        'options' => [
            'disable-javascript' => true,
        ],
        'env'     => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTML_IMG_BINARY', '/usr/local/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
];
