<?php

namespace Artwork\Core\Services;

/**
 * Erkennt öffentlich bekannte Standard-Secrets aus früheren .env.example-/README-Ständen, die
 * Selbst-Hoster oft 1:1 übernehmen (bekannter APP_KEY = Sessions und verschlüsselte Daten fälschbar,
 * bekanntes Reverb-Secret = private Kanäle abhörbar, Events fälschbar). Nur zum Loggen: artwork
 * sperrt deswegen keine Instanz. Reine Config-Vergleiche, billig genug für jeden Request.
 */
class InsecureDefaultsDetector
{
    /**
     * APP_KEYs aus der git-Historie (ad4fc3fe4, 7f8950275, 0b37b9686) und aus phpunit.xml.
     *
     * @var list<string>
     */
    public const KNOWN_APP_KEYS = [
        'base64:sm0ZBzmOOR49uBhvyDYsuckPccHPzA82vdRwSTOU1to=',
        'base64:tVkZTza0DQJH4zQchY6EqN8QwpdgN/LEthLHIfklO7E=',
        'base64:9WLjTcTu1//ZV+hWEyq9al3arZBtzk3/bGFQU4fwxj4=',
        'base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
    ];

    /** @var list<string> */
    public const KNOWN_REVERB_SECRETS = ['app-secret', 'set-a-key-here', 'app-key', 'secret'];

    /** @var list<string> */
    public const KNOWN_DB_PASSWORDS = ['password', 'secret', 'root', 'db'];

    /** @var list<string> */
    public const KNOWN_MEILISEARCH_KEYS = ['ddev', 'masterKey'];

    /**
     * Unsichere Standardwerte einer production-Instanz.
     *
     * @return list<string>
     */
    public function productionFindings(): array
    {
        $findings = [];

        if (in_array((string) config('app.key'), self::KNOWN_APP_KEYS, true)) {
            $findings[] = 'APP_KEY is publicly known (old .env.example/README/phpunit.xml value). '
                . 'Generate a new key with `php artisan key:generate`.';
        }

        if (config('broadcasting.default') === 'reverb') {
            foreach ((array) config('reverb.apps.apps', []) as $app) {
                $secret = trim((string) ($app['secret'] ?? ''));
                if ($secret === '' || in_array($secret, self::KNOWN_REVERB_SECRETS, true)) {
                    $findings[] = 'REVERB_APP_SECRET is empty or a publicly known default. Set a long random value.';
                    break;
                }
            }
        }

        $connection = (string) config('database.default');
        $dbPassword = (string) config("database.connections.{$connection}.password");
        if (in_array($dbPassword, self::KNOWN_DB_PASSWORDS, true)) {
            $findings[] = 'DB_PASSWORD is a publicly known default.';
        }

        if (config('scout.driver') === 'meilisearch') {
            $meilisearchKey = trim((string) config('scout.meilisearch.key'));
            if ($meilisearchKey === '' || in_array($meilisearchKey, self::KNOWN_MEILISEARCH_KEYS, true)) {
                $findings[] = 'MEILISEARCH_KEY is empty or a publicly known default.';
            }
        }

        if (config('app.debug')) {
            $findings[] = 'APP_DEBUG is enabled.';
        }

        return $findings;
    }

    /**
     * APP_ENV ist nicht production, die Instanz läuft aber unter einer öffentlichen Adresse. Dann sind
     * Schutzmechanismen aus, die an production hängen (Demo-Daten, Fehlerseiten, Sentry).
     */
    public function runsNonProductionOnPublicUrl(): bool
    {
        if (app()->environment(['production', 'testing'])) {
            return false;
        }

        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        foreach (['.localhost', '.test', '.local', '.ddev.site', '.internal'] as $localSuffix) {
            if (str_ends_with($host, $localSuffix)) {
                return false;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        return true;
    }
}
