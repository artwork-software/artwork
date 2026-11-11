<?php

namespace Artwork\Modules\Project\Exports;

use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\Table;

/**
 * Veranstaltungsplan und Künstlerplan als Word-Dokument, angelehnt an die Vorlagen des Kunden:
 * Titelzeilen, Abschnitte mit Linie, Zeitplan als Tabelle mit waagerechten Trennlinien.
 * Abschnitte ohne Inhalt werden ausgelassen. Das Dokument ist zum Weiterbearbeiten in Word gedacht
 * (z. B. Konzertablauf ergänzen), daher einfache Tabellen ohne verbundene Zellen.
 *
 * @phpstan-import-type PlanVenue from \Artwork\Modules\Project\Services\Plans\ProjectPlanService
 * @phpstan-import-type PlanTeamMember from \Artwork\Modules\Project\Services\Plans\ProjectPlanService
 * @phpstan-import-type PlanDay from \Artwork\Modules\Project\Services\Plans\ProjectPlanService
 * @phpstan-import-type PlanHotel from \Artwork\Modules\Project\Services\Plans\ProjectPlanService
 */
class ProjectPlanWordWriter
{
    private const FONT = 'Arial';

    /** A4 abzüglich 1,27 cm Rand links und rechts (Vorlagen), in Twips */
    private const CONTENT_WIDTH = 10466;

    private const LINE_COLOR = 'A6A6A6';
    private const MUTED_COLOR = '595959';

    /**
     * @param array{project_name: string, venue: PlanVenue|null, artists: list<string>,
     *     team: list<PlanTeamMember>, days: list<PlanDay>} $plan
     * @return string Pfad der erzeugten Datei
     */
    public function writeEventPlan(array $plan): string
    {
        [$phpWord, $section] = $this->newDocument('Veranstaltungsplan ' . $plan['project_name']);

        $this->writeTitle($section, 'VERANSTALTUNGSPLAN', $plan['project_name'], null);
        $this->writeVenue($section, $plan['venue']);

        if ($plan['artists'] !== []) {
            $this->writeHeading($section, 'Künstler*innen');
            foreach ($plan['artists'] as $artist) {
                $section->addText($this->escape($artist));
            }
        }

        if ($plan['days'] !== []) {
            $this->writeHeading($section, 'Zeitplan');
            $this->writeScheduleTable($section, $plan['days']);
        }

        $this->writeTeam($section, 'Projektteam', $plan['team']);

        return $this->save($phpWord, 'veranstaltungsplan');
    }

    /**
     * @param array{project_name: string, artist_name: string|null, venue: PlanVenue|null,
     *     team: list<PlanTeamMember>, days: list<PlanDay>, hotel: PlanHotel|null} $plan
     * @return string Pfad der erzeugten Datei
     */
    public function writeArtistPlan(array $plan): string
    {
        [$phpWord, $section] = $this->newDocument('Künstlerplan ' . $plan['project_name']);

        $this->writeTitle(
            $section,
            'KÜNSTLERPLAN',
            $plan['project_name'],
            $plan['artist_name'] !== null ? 'für ' . $plan['artist_name'] : null
        );
        $this->writeVenue($section, $plan['venue']);

        foreach ($plan['days'] as $day) {
            $this->writeHeading($section, $day['label'], uppercase: false);
            $this->writeDayTable($section, $day);
        }

        $this->writeHotel($section, $plan['hotel']);
        $this->writeTeam($section, 'Ansprechpersonen', $plan['team']);

        return $this->save($phpWord, 'kuenstlerplan');
    }

    /**
     * @return array{0: PhpWord, 1: Section}
     */
    private function newDocument(string $title): array
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(10);
        $phpWord->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        // Dokumenteigenschaften schreibt PhpWord selbst maskiert, daher nur Steuerzeichen entfernen
        $phpWord->getDocInfo()->setTitle($this->withoutControlCharacters($title));
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::DE_DE));

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        return [$phpWord, $section];
    }

    private function writeTitle(Section $section, string $planTitle, string $projectName, ?string $subtitle): void
    {
        $section->addText($this->escape($planTitle), ['bold' => true, 'size' => 18]);
        $section->addText($this->escape('»' . $projectName . '«'), ['bold' => true, 'size' => 15]);
        if ($subtitle !== null) {
            $section->addText($this->escape($subtitle), ['size' => 12], ['spaceBefore' => 60]);
        }
    }

    /**
     * @param PlanVenue|null $venue
     */
    private function writeVenue(Section $section, ?array $venue): void
    {
        if ($venue === null) {
            return;
        }

        $this->writeHeading($section, 'Spielstätte');
        $section->addText($this->escape($venue['name']), ['bold' => true]);
        if ($venue['address'] !== null) {
            $section->addText($this->escape($venue['address']));
        }
    }

    private function writeHeading(Section $section, string $text, bool $uppercase = true): void
    {
        $section->addText(
            $this->escape($uppercase ? mb_strtoupper($text) : $text),
            ['bold' => true, 'size' => 12],
            [
                'spaceBefore' => 360,
                'spaceAfter' => 120,
                'keepNext' => true,
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
            ]
        );
    }

    /**
     * Zeitplan des Veranstaltungsplans: Datum (nur in der ersten Zeile des Tages) | Uhrzeit | Termin.
     *
     * @param list<PlanDay> $days
     */
    private function writeScheduleTable(Section $section, array $days): void
    {
        $widths = [1900, 1800, self::CONTENT_WIDTH - 3700];
        $table = $section->addTable($this->tableStyle());

        foreach ($days as $dayIndex => $day) {
            foreach ($day['entries'] as $entryIndex => $entry) {
                $isFirstOfDay = $entryIndex === 0;
                // Neuer Tag: kräftigere Linie, sonst feine Trennlinie
                $cellStyle = $isFirstOfDay && $dayIndex > 0
                    ? ['borderTopSize' => 8, 'borderTopColor' => '000000']
                    : ($entryIndex > 0 ? ['borderTopSize' => 4, 'borderTopColor' => self::LINE_COLOR] : []);

                $table->addRow(null, ['cantSplit' => true]);
                $dateCell = $table->addCell($widths[0], $this->cellStyle($cellStyle));
                if ($isFirstOfDay) {
                    $dateCell->addText($this->escape($day['label']), ['bold' => true]);
                }
                $table->addCell($widths[1], $this->cellStyle($cellStyle))->addText($this->escape($entry['time']));
                $this->writeEntry($table->addCell($widths[2], $this->cellStyle($cellStyle)), $entry);
            }
        }
    }

    /**
     * Ein Tag des Künstlerplans: Uhrzeit | Termin mit Ort und Adresse.
     *
     * @param PlanDay $day
     */
    private function writeDayTable(Section $section, array $day): void
    {
        $widths = [1800, self::CONTENT_WIDTH - 1800];
        $table = $section->addTable($this->tableStyle());

        foreach ($day['entries'] as $entryIndex => $entry) {
            $cellStyle = $entryIndex > 0 ? ['borderTopSize' => 4, 'borderTopColor' => self::LINE_COLOR] : [];
            $table->addRow(null, ['cantSplit' => true]);
            $table->addCell($widths[0], $this->cellStyle($cellStyle))
                ->addText($this->escape($entry['time']), ['bold' => true]);
            $this->writeEntry($table->addCell($widths[1], $this->cellStyle($cellStyle)), $entry);
        }
    }

    /**
     * @param array{time: string, title: string, description: string|null, place: string|null,
     *     address: string|null} $entry
     */
    private function writeEntry(Cell $cell, array $entry): void
    {
        if ($entry['title'] !== '') {
            $cell->addText($this->escape($entry['title']), ['bold' => true]);
        }
        $this->addLines($cell, $entry['description']);

        $place = implode(', ', array_filter([$entry['place'], $entry['address']]));
        if ($place !== '') {
            $cell->addText($this->escape($place), ['italic' => true, 'color' => self::MUTED_COLOR]);
        }
    }

    /**
     * @param PlanHotel|null $hotel
     */
    private function writeHotel(Section $section, ?array $hotel): void
    {
        if ($hotel === null) {
            return;
        }

        $rows = array_filter([
            'Unterkunft' => $hotel['name'],
            'Adresse' => $hotel['address'],
            'Telefon' => $hotel['phone'],
            'Anreise' => $hotel['arrival'],
            'Abreise' => $hotel['departure'],
            'Nächte' => $hotel['nights'] !== null
                ? $hotel['nights'] . ' ' . ($hotel['nights'] === 1 ? 'Nacht' : 'Nächte')
                : null,
            'Zimmerart' => $hotel['room_type'],
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        $this->writeHeading($section, 'Unterkunft');
        $table = $section->addTable($this->tableStyle());
        foreach ($rows as $label => $value) {
            $table->addRow();
            $table->addCell(1800, $this->cellStyle())
                ->addText($this->escape($label), ['color' => self::MUTED_COLOR]);
            $table->addCell(self::CONTENT_WIDTH - 1800, $this->cellStyle())->addText(
                $this->escape($value),
                ['bold' => $label === 'Unterkunft']
            );
        }
    }

    /**
     * @param list<PlanTeamMember> $team
     */
    private function writeTeam(Section $section, string $heading, array $team): void
    {
        if ($team === []) {
            return;
        }

        $this->writeHeading($section, $heading);
        $widths = [4200, 2600, self::CONTENT_WIDTH - 6800];
        $table = $section->addTable($this->tableStyle());

        foreach ($team as $index => $member) {
            $cellStyle = $index > 0 ? ['borderTopSize' => 4, 'borderTopColor' => self::LINE_COLOR] : [];
            $table->addRow(null, ['cantSplit' => true]);

            $nameCell = $table->addCell($widths[0], $this->cellStyle($cellStyle));
            $nameCell->addText($this->escape($member['name']), ['bold' => true]);
            if ($member['function'] !== null) {
                $nameCell->addText($this->escape($member['function']), ['color' => self::MUTED_COLOR]);
            }

            $phoneCell = $table->addCell($widths[1], $this->cellStyle($cellStyle));
            if ($member['phone'] !== null) {
                $phoneCell->addText($this->escape('Tel. ' . $member['phone']));
            }

            $emailCell = $table->addCell($widths[2], $this->cellStyle($cellStyle));
            if ($member['email'] !== null) {
                $emailCell->addText($this->escape($member['email']));
            }
        }
    }

    /**
     * PhpWord setzt sonst <w:noWrap/>: lange Beschreibungen liefen in Word über die Zelle hinaus.
     *
     * @param array<string, mixed> $style
     * @return array<string, mixed>
     */
    private function cellStyle(array $style = []): array
    {
        return ['noWrap' => false, 'valign' => 'top', ...$style];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableStyle(): array
    {
        return [
            'unit' => TblWidth::TWIP,
            'width' => self::CONTENT_WIDTH,
            'layout' => Table::LAYOUT_FIXED,
            'cellMarginTop' => 50,
            'cellMarginBottom' => 50,
            'cellMarginLeft' => 60,
            'cellMarginRight' => 60,
        ];
    }

    /**
     * Mehrzeilige Texte als einzelne Absätze: PhpWord setzt Umbrüche in TextRuns außerhalb von
     * <w:r> ab (ungültiges OOXML).
     */
    private function addLines(Cell $cell, ?string $text): void
    {
        if ($text === null) {
            return;
        }

        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", trim($text))) as $line) {
            $cell->addText($this->escape($line));
        }
    }

    private function save(PhpWord $phpWord, string $prefix): string
    {
        // Eindeutiger Name statt tempnam(): tempnam legt die Datei ohne Endung an, die sonst liegen bleibt
        $path = sys_get_temp_dir() . '/' . $prefix . '-' . Str::uuid()->toString() . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * Ohne aktiviertes Output-Escaping (Standard) schreibt PhpWord Text roh ins XML; Steuerzeichen
     * (außer Tab) machen die Datei zusätzlich unlesbar.
     */
    private function escape(string $text): string
    {
        $text = $this->withoutControlCharacters($text);

        if (Settings::isOutputEscapingEnabled()) {
            return $text;
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function withoutControlCharacters(string $text): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    }
}
