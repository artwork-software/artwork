<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Settings\ShiftSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Branding "Artwork Testhaus": Firmenname, Briefkopf, Demo-Logo/-Banner und
 * demofreundliche Schicht-Settings. Bewusst konservativ: individuell
 * hochgeladene Logos oder geänderte Namen werden nicht überschrieben.
 */
class DemoBrandingSeeder extends Seeder
{
    private const BIG_LOGO_PATH = 'logo/testhaus_logo_big.svg';
    private const SMALL_LOGO_PATH = 'logo/testhaus_logo_small.svg';
    private const BANNER_PATH = 'banner/testhaus_banner.svg';

    /** Werte, die als "noch nie angefasst" gelten und ersetzt werden dürfen. */
    private const DEFAULT_LOGO_PATHS = [
        '', 'logo/artwork_logo_big.svg', 'logo/artwork_logo_small.svg',
        'public/logo/artwork_logo_big.svg', 'public/logo/artwork_logo_small.svg',
    ];
    private const DEFAULT_BANNER_PATHS = ['', 'banner/default_banner.svg', 'public/banner/default_banner.svg'];
    private const DEFAULT_COMPANY_NAMES = ['', 'artwork', 'Artwork', 'artwork.software'];

    public function run(): void
    {
        $settings = app(GeneralSettings::class);

        if (in_array(trim($settings->company_name), self::DEFAULT_COMPANY_NAMES, true)) {
            $settings->company_name = DemoDataPools::COMPANY_NAME;
            $settings->page_title = DemoDataPools::COMPANY_NAME;
            $settings->business_name = DemoDataPools::COMPANY_NAME . ' gGmbH';
            $settings->impressum_link = 'https://artwork.software/impressum';
            $settings->privacy_link = 'https://artwork.software/datenschutz';
            $settings->letterhead_name = DemoDataPools::COMPANY_NAME . ' gGmbH';
            $settings->letterhead_street = 'Speicherstraße 12';
            $settings->letterhead_zip_code = '20457';
            $settings->letterhead_city = 'Hamburg';
            $settings->letterhead_email = 'demo@' . DemoDataPools::EMAIL_DOMAIN;
            $this->command?->info('Branding: Firmenname/Briefkopf auf "Artwork Testhaus" gesetzt.');
        } else {
            $this->command?->warn(
                sprintf('Branding: Firmenname "%s" bereits gepflegt – bleibt unangetastet.', $settings->company_name)
            );
        }

        $this->seedLogos($settings);
        $settings->save();

        $this->seedShiftSettings();
    }

    private function seedLogos(GeneralSettings $settings): void
    {
        $disk = Storage::disk('public');
        $disk->put(self::BIG_LOGO_PATH, $this->bigLogoSvg());
        $disk->put(self::SMALL_LOGO_PATH, $this->smallLogoSvg());
        $disk->put(self::BANNER_PATH, $this->bannerSvg());

        if (in_array($settings->big_logo_path, [...self::DEFAULT_LOGO_PATHS, self::BIG_LOGO_PATH], true)) {
            $settings->big_logo_path = self::BIG_LOGO_PATH;
            $settings->small_logo_path = self::SMALL_LOGO_PATH;
            $this->command?->info('Branding: Demo-Logos gesetzt.');
        } else {
            $this->command?->warn('Branding: eigenes Logo erkannt – Demo-Logo nur abgelegt, nicht aktiviert.');
        }

        if (in_array($settings->banner_path, [...self::DEFAULT_BANNER_PATHS, self::BANNER_PATH], true)) {
            $settings->banner_path = self::BANNER_PATH;
        }
    }

    private function seedShiftSettings(): void
    {
        $general = app(GeneralSettings::class);
        $general->warn_multiple_assignments = true;
        // Festschreibungs-Workflow an: die KW-Anfragen (angefragt/bestätigt) sind Teil der Demo
        $general->shift_commit_workflow_enabled = true;
        $general->save();

        $shiftSettings = app(ShiftSettings::class);
        $shiftSettings->shift_confirmation_enabled = true;
        $shiftSettings->allow_shift_overbooking = true;
        $shiftSettings->save();

        $this->command?->info(
            'Settings: Mehrfacheinsatz-Warnung, Schichtbestätigung und Überbuchung für die Demo aktiviert.'
        );
    }

    // Farbwelt "artwork core" (Design-Basis-Moodboard): Ink #27233C, Papier #EDECE8,
    // Markenorange #EB7A3D (als Text auf hell: #B3541A), Nacht #151320, Neutral-40 #A7A6B1.
    // Der Bogen ist die Original-Konstruktion aus der artwork-Wortmarke: ein Viertelring
    // (aussen R, innen R/2), der dort das "a" ersetzt.
    private function bigLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 72" width="260" height="72">
  <path d="M8,48 A26,26 0 0 1 34,22 L34,35 A13,13 0 0 0 21,48 Z" fill="#EB7A3D"/>
  <text x="38" y="48" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif" font-size="34"
        fill="#27233C"><tspan font-weight="600">test</tspan><tspan font-weight="300">haus</tspan></text>
  <rect x="196" y="26" width="58" height="20" rx="10" fill="#B3541A"/>
  <text x="225" y="40" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="12" font-weight="700" fill="#FFFFFF">DEMO</text>
</svg>
SVG;
    }

    private function smallLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <rect x="0" y="0" width="64" height="64" rx="14" fill="#27233C"/>
  <path d="M19,37 A26,26 0 0 1 45,11 L45,24 A13,13 0 0 0 32,37 Z" fill="#EB7A3D"/>
  <rect x="9" y="47" width="46" height="13" rx="6.5" fill="#EDECE8"/>
  <text x="32" y="56.5" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="8.5" font-weight="700" letter-spacing="1.5" fill="#27233C">DEMO</text>
</svg>
SVG;
    }

    // Hochformat mit zentriertem Inhalt: der Login zeigt das Banner per object-cover
    // auf einer vollen Bildschirmhaelfte, alles ausserhalb der Mitte wird beschnitten.
    private function bannerSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 1200" width="900" height="1200">
  <rect width="900" height="1200" fill="#27233C"/>
  <circle cx="120" cy="1160" r="380" fill="#151320"/>
  <circle cx="820" cy="40" r="260" fill="#151320"/>
  <circle cx="450" cy="480" r="62" fill="#FCFCFB"/>
  <path d="M420,510 A60,60 0 0 1 480,450 L480,480 A30,30 0 0 0 450,510 Z" fill="#27233C"/>
  <text x="450" y="640" text-anchor="middle" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="64" fill="#F4F4F2"><tspan font-weight="600">artwork</tspan><tspan font-weight="300"> Testhaus</tspan></text>
  <text x="450" y="690" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="22" fill="#A7A6B1">Demo-Umgebung – alle Daten sind fiktiv</text>
  <rect x="402" y="726" width="96" height="34" rx="17" fill="#EB7A3D"/>
  <text x="450" y="749" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="15" font-weight="700" letter-spacing="2" fill="#27233C">DEMO</text>
  <text x="450" y="840" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="16" fill="#A7A6B1" opacity="0.8">powered by artwork</text>
</svg>
SVG;
    }
}
