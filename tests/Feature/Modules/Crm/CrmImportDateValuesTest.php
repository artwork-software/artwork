<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Datumswerte im CRM-Import: jede unterstützte Schreibweise landet als dasselbe Datum, alles andere
 * überspringt die Zeile mit Meldung und wird nie zu einem geratenen Datum.
 */
final class CrmImportDateValuesTest extends FeatureTestCase
{
    /**
     * @return array{CrmContactType, CrmProperty}
     */
    private function contactTypeWithDateProperty(): array
    {
        $type = CrmContactType::factory()->create();
        $property = CrmProperty::factory()->create(['name' => 'Geburtstag', 'type' => 'date']);
        $type->properties()->attach($property->id, ['sort_order' => 0]);

        return [$type, $property];
    }

    /**
     * @return array<string, mixed>
     */
    private function import(UploadedFile $file, CrmContactType $type, CrmProperty $property): array
    {
        $this->post(route('crm.import.upload'), [
            'file' => $file,
            'use_type_column' => '0',
            'crm_contact_type_id' => $type->id,
        ])->assertOk();

        $this->post(route('crm.import.execute'), [
            'mapping' => [
                'display_name' => 0,
                'properties' => [(string) $property->id => 1],
            ],
            'duplicates' => null,
        ])->assertRedirect();

        return session('importResult');
    }

    private function storedDate(CrmContactType $type, CrmProperty $property, string $displayName): ?string
    {
        $contact = CrmContact::query()
            ->where('crm_contact_type_id', $type->id)
            ->where('display_name', $displayName)
            ->first();

        return $contact === null ? null : CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->where('crm_property_id', $property->id)
            ->value('value');
    }

    #[Test]
    public function text_notations_are_read_strictly_and_invalid_ones_skip_the_row(): void
    {
        $this->actingAsAdmin();
        [$type, $property] = $this->contactTypeWithDateProperty();

        $csv = "Name,Geburtstag\n"
            . "Lang,15.03.2026\n"
            . "Kurz,15.03.26\n"
            . "Iso,2026-03-15\n"
            . "Schraeg,31/12/2026\n"
            . "Amerikanisch,12/31/2026\n"
            . "Text,morgen\n";

        $result = $this->import(UploadedFile::fake()->createWithContent('kontakte.csv', $csv), $type, $property);

        $this->assertSame(4, $result['created']);
        $this->assertSame([6, 7], array_column($result['skipped'], 'row'));
        $this->assertSame('2026-03-15', $this->storedDate($type, $property, 'Lang'));
        $this->assertSame('2026-03-15', $this->storedDate($type, $property, 'Kurz'));
        $this->assertSame('2026-03-15', $this->storedDate($type, $property, 'Iso'));
        $this->assertSame('2026-12-31', $this->storedDate($type, $property, 'Schraeg'));
        $this->assertNull($this->storedDate($type, $property, 'Amerikanisch'));
        $this->assertNull($this->storedDate($type, $property, 'Text'));
    }

    #[Test]
    public function excel_date_cells_with_and_without_time_are_read_as_their_date(): void
    {
        $this->actingAsAdmin();
        [$type, $property] = $this->contactTypeWithDateProperty();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([['Name', 'Geburtstag']]);
        $sheet->setCellValue('A2', 'Datum');
        $sheet->setCellValue('B2', ExcelDate::PHPToExcel(new \DateTime('2026-03-15 00:00:00')));
        $sheet->getStyle('B2')->getNumberFormat()->setFormatCode('dd.mm.yyyy');
        $sheet->setCellValue('A3', 'Zeitpunkt');
        $sheet->setCellValue('B3', ExcelDate::PHPToExcel(new \DateTime('2026-03-15 14:30:00')));
        $sheet->getStyle('B3')->getNumberFormat()->setFormatCode('dd.mm.yyyy hh:mm');

        $path = tempnam(sys_get_temp_dir(), 'crm-import') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $file = new UploadedFile(
                $path,
                'kontakte.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );
            $result = $this->import($file, $type, $property);
        } finally {
            @unlink($path);
        }

        $this->assertSame(2, $result['created']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame('2026-03-15', $this->storedDate($type, $property, 'Datum'));
        $this->assertSame('2026-03-15', $this->storedDate($type, $property, 'Zeitpunkt'));
    }
}
