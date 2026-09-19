<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;

/**
 * Vorlagen für Projekt-Tabs (Einstellungen → Tab-Einstellungen → „Aus Vorlage anlegen“).
 *
 * Eine Vorlage ist nur eine Arbeitserleichterung: Beim Anwenden entstehen ein GANZ NORMALER Tab und
 * ganz normale Komponenten (nicht „special“, voll bearbeitbar, löschbar, verschiebbar). Es gibt keinen
 * Vorlagen-Zustand am Ergebnis. Vorlagen enthalten bewusst keine hausspezifischen Daten.
 *
 * Aufbau eines Eintrags:
 *  - key, name, description, prerequisites (Texte = Übersetzungsschlüssel, siehe lang/de.json)
 *  - components: Liste in Reihenfolge; entweder
 *      ['type' => Enum-Wert, 'name' => ..., 'data' => [...], 'note' => ...]  → neue Komponente
 *    oder
 *      ['special' => Enum-Wert, 'note' => ...]  → vorhandene System-Komponente
 */
final class ProjectTabTemplateCatalog
{
    public const PRODUCTION_INQUIRY = 'production_inquiry';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::PRODUCTION_INQUIRY => self::productionInquiry(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Abfrageformular Produktion: die Fragen, die ein Haus typischerweise vor einem Gastspiel von
     * Künstler*innen/Compagnies einholt (Kontakt, Besetzung, Dauer, Technik-Dokumente, Musikliste,
     * Texte, Bild-/Videomaterial, Sponsoren). Gedacht als Tab, der per „Externe einladen“ direkt
     * von den Gästen ausgefüllt wird.
     *
     * @return array<string, mixed>
     */
    private static function productionInquiry(): array
    {
        $title = static fn (string $name): array => [
            'type' => ProjectTabComponentEnum::TITLE->value,
            'name' => $name,
            'data' => ['title' => $name, 'title_size' => 16],
        ];
        $text = static fn (string $name, string $placeholder = '', string $note = ''): array => [
            'type' => ProjectTabComponentEnum::TEXT_FIELD->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => $placeholder],
            'note' => $note,
        ];
        $area = static fn (string $name, string $placeholder = '', string $note = ''): array => [
            'type' => ProjectTabComponentEnum::TEXT_AREA->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => $placeholder],
            'note' => $note,
        ];
        $link = static fn (string $name, string $note = ''): array => [
            'type' => ProjectTabComponentEnum::LINK->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => 'https://…'],
            'note' => $note,
        ];

        return [
            'key' => self::PRODUCTION_INQUIRY,
            'name' => 'Production inquiry',
            'description' => 'Tab for exchanging project information with artists and companies: '
                . 'contact and cast, general facts, '
                . 'documents (bios, dossier, technical rider), music list, texts, image and video material, sponsors. '
                . 'This tab is an example for the external access feature.',
            'prerequisites' => 'To let guests fill in this tab themselves, enable "External access" '
                . 'under Settings → External access '
                . 'and grant the permission "Invite externals". People with that permission then find the button '
                . '"Invite external to this tab" on the tab and can invite artists or companies by email; '
                . 'the guests fill in the fields, upload documents and submit the data.',
            'components' => [
                $title('Contact & company'),
                // mehrzeilige Angaben bewusst als Textbereich, damit intern wie extern alles sichtbar ist
                $area('Name and contractual address of the company', 'Exact legal name, street, zip, city, country'),
                $area('Contact person for the production', 'Name, role, email, phone'),
                $text('Email address for all queries', 'Valid address for all queries'),

                $title('Artists'),
                $text('Number of arriving artists', 'Number, incl. crew and production'),
                $area('Names of everyone arriving', 'Name, function, email, phone – one person per line'),
                $area('Social media handles and further links', 'Website, Instagram, …'),

                $title('General information'),
                $text('Title of the production'),
                $text('Duration of the production (minutes, without break)'),
                $text('Age recommendation', 'e.g. from age 12, 0 = none'),
                $text('Language of the performance', '"none" if without text'),

                $title('Documents'),
                [
                    'special' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
                    'note' => 'Please upload as PDF: short bios of the artists, dossier, technical rider '
                    . 'and, if available, '
                . 'the description of an additional educational offer. Images and sponsor logos '
                . '(high resolution, vector or PNG) can be uploaded here as well.',
                ],
                [
                    'type' => ProjectTabComponentEnum::CHECKBOX->value,
                    'name' => 'Additional educational offer available',
                    'data' => ['label' => 'Additional educational offer available', 'checked' => false],
                    'note' => 'If yes: please attach the description as PDF in the documents above.',
                ],

                $title('Music & rights'),
                $area(
                    'List of music used in the performance',
                    'Title – composer – performer – duration',
                    'Needed for the registration with the collecting society.',
                ),

                $title('Anything else'),
                [
                    'type' => ProjectTabComponentEnum::DROPDOWN->value,
                    'name' => 'Wardrobe service required?',
                    'data' => [
                        'label' => 'Wardrobe service required?',
                        'options' => [['value' => 'Yes'], ['value' => 'No'], ['value' => 'Other']],
                        'selected' => '',
                    ],
                    'note' => 'If "Other": please describe in the remarks.',
                ],
                $area('Other remarks'),

                $title('Texts & description'),
                $area('Short description / teaser', 'max. 300 characters'),
                $text('Subtitle and credits', 'incl. credits (choreography, music, light, costume …)'),
                $area('Abstract for the production', '500–3000 characters'),

                $title('Images & video'),
                $link('Link to download the images', 'Download link without expiry date.'),
                $area('Photo credits', 'per image, exactly as they should appear'),
                $link('Trailer / teaser link', 'YouTube or Vimeo, at least Full HD.'),
                $area('Video credits'),

                $title('Sponsors & partners'),
                $area('Sponsors, supporters, partners', 'complete wording as it should appear'),
            ],
        ];
    }
}
