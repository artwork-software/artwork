<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Basis der Browser-Tests (core-ki-bar-stub-test).
 *
 * Der ddev-Container ist arm64; fuer Linux-arm64 gibt es kein Chrome-for-Testing, deshalb wird
 * Debians `chromium` mit `chromium-driver` benutzt statt der von Dusk heruntergeladenen Treiber.
 * Der Browser ist bewusst **nicht** Teil des Repository-Images (Entscheidung 15.09.2026):
 * fehlt der Treiber — in der CI, in `production`, auf einem frischen Rechner —, ueberspringen
 * sich die Tests mit klarer Meldung, statt rot zu werden.
 *
 * Die Anwendung unter Test laeuft nicht unter der normalen Projekt-URL, sondern unter einem
 * eigenen `php artisan serve` mit gesetzten Umgebungsvariablen (siehe
 * IMPLEMENTATION_LOG_core-ki-bar-stub-test.md §1). `DUSK_APP_URL` zeigt darauf.
 */
abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    private const CHROMEDRIVER_PATH = '/usr/bin/chromedriver';

    private const CHROMIUM_PATH = '/usr/bin/chromium';

    #[BeforeClass]
    public static function prepare(): void
    {
        if (!static::browserAvailable()) {
            return;
        }

        static::useChromedriver(self::CHROMEDRIVER_PATH);
        static::startChromeDriver(['--port=9515']);
    }

    public static function browserAvailable(): bool
    {
        return is_executable(self::CHROMEDRIVER_PATH) && is_executable(self::CHROMIUM_PATH);
    }

    protected function setUp(): void
    {
        if (!static::browserAvailable()) {
            $this->markTestSkipped(
                'Kein Browser vorhanden (' . self::CHROMIUM_PATH . ' / ' . self::CHROMEDRIVER_PATH . '). '
                . 'Lokal per .ddev/web-build/Dockerfile.chromium nachruesten.'
            );
        }

        parent::setUp();
    }

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions())
            ->setBinary(self::CHROMIUM_PATH)
            ->addArguments([
                '--window-size=1600,1000',
                '--disable-gpu',
                '--headless=new',
                '--no-sandbox',
                // Im Container laeuft /dev/shm zu klein fuer Chromium.
                '--disable-dev-shm-usage',
                // Der Vite-Dev-Server liefert die Module ueber die ddev-URL mit eigenem CA.
                '--ignore-certificate-errors',
                '--allow-insecure-localhost',
            ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }

    protected function baseUrl(): string
    {
        return rtrim((string) (env('DUSK_APP_URL') ?: 'http://127.0.0.1:8099'), '/');
    }
}
