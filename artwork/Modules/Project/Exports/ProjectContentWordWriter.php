<?php

namespace Artwork\Modules\Project\Exports;

use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

/**
 * Projektinhalte als Word: pro Projekt ein Block aus einfachen Absätzen, auf Wunsch mit fetter Bezeichnung
 * über dem Wert. Bewusst ohne Tabellen, Tabstops und Formatvorlagen (Wunsch der Abteilung: so wenig
 * Gestaltung wie möglich, die Texte werden weiterverarbeitet).
 */
class ProjectContentWordWriter
{
    /**
     * @param array<int, array<int, array{title: string, show_label: bool, value: string}>> $blocks
     * @return string Pfad der erzeugten Datei
     */
    public function write(array $blocks): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $phpWord->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

        $section = $phpWord->addSection();

        foreach ($blocks as $blockIndex => $cells) {
            if ($blockIndex > 0) {
                $section->addTextBreak(2);
            }
            $this->writeBlock($section, $cells);
        }

        // Eindeutiger Name statt tempnam(): tempnam legt die Datei ohne Endung an, die sonst liegen bleibt
        $path = sys_get_temp_dir() . '/project-content-' . Str::uuid()->toString() . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * @param array<int, array{title: string, show_label: bool, value: string}> $cells
     */
    private function writeBlock(Section $section, array $cells): void
    {
        $isFirst = true;
        foreach ($cells as $cell) {
            if (trim($cell['value']) === '') {
                continue;
            }

            if ($cell['show_label']) {
                // Leerzeile vor jedem beschrifteten Inhalt, damit die Abschnitte getrennt stehen
                if (!$isFirst) {
                    $section->addTextBreak();
                }
                $section->addText($this->escape($cell['title']), ['bold' => true]);
            }

            // Jede Zeile als eigener Absatz ohne Abstand: PhpWord setzt Umbrüche in TextRuns außerhalb von
            // <w:r> ab (ungültiges OOXML), Absätze lassen sich in Word zudem leichter weiterverarbeiten
            foreach (explode("\n", $cell['value']) as $line) {
                $section->addText($this->escape($line));
            }

            $isFirst = false;
        }
    }

    /**
     * Ohne aktiviertes Output-Escaping (Standard) schreibt PhpWord Text roh ins XML.
     */
    private function escape(string $text): string
    {
        if (Settings::isOutputEscapingEnabled()) {
            return $text;
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
