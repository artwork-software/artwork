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
 *  - Bei CRM-Kontaktlisten nennt data.contact_type_slugs die erlaubten Kontakttypen; beim Anwenden werden
 *    daraus die IDs der Instanz (contact_type_ids).
 */
final class ProjectTabTemplateCatalog
{
    public const PRODUCTION_INQUIRY = 'production_inquiry';

    /** Farbe der Abschnittsbalken der Vorlage (Rosa wie im Abfrageformular des Kunden). */
    public const PRODUCTION_INQUIRY_BAR_COLOR = '#FFB6E1';

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
     * Komponente „Anreisende Personen“ der Vorlage „Abfrage Produktion“ (auch für die Umstellung
     * bestehender Tabs, siehe ProductionInquiryArrivingPersonsUpgrade).
     *
     * @return array<string, mixed>
     */
    public static function arrivingPersonsDefinition(): array
    {
        return [
            'type' => ProjectTabComponentEnum::CRM_CONTACT_LIST->value,
            'name' => 'Names of everyone arriving',
            'data' => [
                'title' => 'Names of everyone arriving',
                'description' => 'Please add every person travelling with the production, each as their own contact.',
                'contact_type_slugs' => ['artist'],
                'max_contacts' => null,
            ],
            // Erstfassung: Textbereich gleichen Namens (siehe ProductionInquiryTemplateUpgrade)
            'legacy' => 'Names of everyone arriving',
        ];
    }

    /**
     * Abfrageformular Produktion (Stand 2): die Fragen, die ein Haus typischerweise vor einem Gastspiel
     * von Künstler*innen/Compagnies einholt — gegliedert in farbige Abschnitte, Hinweise stehen sichtbar
     * unter den Feldern (Notiz), anreisende Personen sind echte CRM-Kontakte. Gedacht als Tab, der per
     * „Externe einladen" direkt von den Gästen ausgefüllt wird.
     *
     * 'legacy' = Name der Komponente in der ersten Vorlagenfassung; darüber stellt
     * ProductionInquiryTemplateUpgrade unveränderte Bestands-Tabs auf diesen Stand um (Werte bleiben).
     *
     * @return array<string, mixed>
     */
    private static function productionInquiry(): array
    {
        $section = static fn (string $name, string $subtitle = '', ?string $legacy = null): array => [
            'type' => ProjectTabComponentEnum::TITLE->value,
            'name' => $name,
            'data' => [
                'title' => $name,
                'title_size' => 15,
                'subtitle' => $subtitle,
                'bar_color' => self::PRODUCTION_INQUIRY_BAR_COLOR,
            ],
            'legacy' => $legacy ?? $name,
        ];
        $text = static fn (string $name, string $note = '', ?string $legacy = null): array => [
            'type' => ProjectTabComponentEnum::TEXT_FIELD->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => ''],
            'note' => $note,
            'legacy' => $legacy ?? $name,
        ];
        $area = static fn (string $name, string $note = '', ?string $legacy = null): array => [
            'type' => ProjectTabComponentEnum::TEXT_AREA->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => ''],
            'note' => $note,
            'legacy' => $legacy ?? $name,
        ];
        $link = static fn (string $name, string $note = ''): array => [
            'type' => ProjectTabComponentEnum::LINK->value,
            'name' => $name,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => 'https://…'],
            'note' => $note,
            'legacy' => $name,
        ];

        return [
            'key' => self::PRODUCTION_INQUIRY,
            'name' => 'Production inquiry',
            'description' => 'Tab for exchanging project information with artists and companies, divided into colored '
                . 'sections: contact and company, arriving persons (as CRM contacts), general facts, documents '
                . '(bios, dossier, technical rider), music list, texts, image and video material, sponsors. '
                . 'Hints are shown directly below the fields. This tab is an example for the external access feature.',
            'prerequisites' => 'To let guests fill in this tab themselves, enable "External access" '
                . 'under Settings → External access '
                . 'and grant the permission "Invite externals". People with that permission then find the button '
                . '"Invite external to this tab" on the tab and can invite artists or companies by email; '
                . 'the guests fill in the fields, add the arriving persons, upload documents and submit the data.',
            'components' => [
                [
                    'type' => ProjectTabComponentEnum::TITLE->value,
                    'name' => 'Production information form',
                    'data' => [
                        'title' => 'Production information form',
                        'title_size' => 20,
                        'subtitle' => 'Please fill in all fields. Your entries are saved automatically. '
                            . 'Once everything is complete, click "Submit entered data" at the bottom.',
                        'bar_color' => '',
                    ],
                ],

                $section('Contact & company'),
                $area('Name and contractual address of the company', 'Exact legal name, street, zip, city, country'),
                $area('Contact person for the production', 'Name, role, email, phone'),
                $text('Email address for all queries', 'Valid address for all queries'),

                $section('Artists'),
                $text('Number of arriving artists', 'Number, incl. crew and production'),
                self::arrivingPersonsDefinition(),
                $area('Social media handles and further links', 'Website, Instagram, …'),

                $section('General information'),
                $text('Title of the production'),
                $text(
                    'Duration of the production',
                    'In minutes, without break',
                    'Duration of the production (minutes, without break)',
                ),
                $text('Age recommendation', 'e.g. from age 12, 0 = none'),
                $text('Language of the performance', '"none" if without text'),

                $section('Documents'),
                [
                    'special' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
                    'note' => 'Please upload as PDF: short bios of the artists, dossier, technical rider and, '
                        . 'if available, the description of an additional educational offer. Images and sponsor '
                        . 'logos (high resolution, vector or PNG) can be uploaded here as well.',
                    'legacy' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value,
                ],
                [
                    'type' => ProjectTabComponentEnum::CHECKBOX->value,
                    'name' => 'Additional educational offer available',
                    'data' => ['label' => 'Additional educational offer available', 'checked' => false],
                    'note' => 'If yes: please attach the description as PDF in the documents above.',
                    'legacy' => 'Additional educational offer available',
                ],

                $section('Music & rights'),
                $area(
                    'List of music used in the performance',
                    'Title – composer – performer – duration. Needed for the registration with the collecting society.',
                ),

                $section('Anything else'),
                [
                    'type' => ProjectTabComponentEnum::DROPDOWN->value,
                    'name' => 'Wardrobe service required?',
                    'data' => [
                        'label' => 'Wardrobe service required?',
                        'options' => [['value' => 'Yes'], ['value' => 'No'], ['value' => 'Other']],
                        'selected' => '',
                    ],
                    'note' => 'If "Other": please describe in the remarks.',
                    'legacy' => 'Wardrobe service required?',
                ],
                $area('Other remarks'),

                $section('Texts & description'),
                $area('Short description / teaser', 'max. 300 characters'),
                $text('Subtitle and credits', 'incl. credits (choreography, music, light, costume …)'),
                $area('Abstract for the production', '500–3000 characters'),

                $section('Marketing material: images & video', '', 'Images & video'),
                $link('Link to download the images', 'Download link without expiry date.'),
                $area('Photo credits', 'Per image, exactly as they should appear'),
                $link('Trailer / teaser link', 'YouTube or Vimeo, at least Full HD.'),
                $area('Video credits'),

                $section('Sponsors & partners'),
                $area(
                    'Sponsors, supporters, partners',
                    'Complete wording as it should appear. Please upload the logos (high resolution, vector or PNG) '
                    . 'in the documents above.',
                ),
            ],
        ];
    }
}
