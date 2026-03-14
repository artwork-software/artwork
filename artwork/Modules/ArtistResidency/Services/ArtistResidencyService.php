<?php

namespace Artwork\Modules\ArtistResidency\Services;

use Artwork\Core\Enums\ExportType;
use Artwork\Modules\ArtistResidency\Exports\ArtistResidencyExcelExport;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\ArtistResidency\Repositories\ArtistRepository;
use Artwork\Modules\ArtistResidency\Repositories\ArtistResidencyRepository;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

readonly class ArtistResidencyService
{
    public function __construct(
        private ArtistResidencyRepository $artistResidencyRepository,
        private PdfWrapper $pdf,
        private FilesystemManager $filesystemManager,
        private InertiaResponseFactory $inertiaResponseFactory,
        private ResponseFactory $responseFactory,
        private AuthManager $authManager,
        protected ArtistResidencyRepository $residencies,
        protected ArtistRepository $artists,
    ) {
    }


    /** Erstellen + sichere Artist-Verknüpfung/Neuanlage */
    public function create(array $payload): ArtistResidency
    {
        return DB::transaction(function () use ($payload) {
            [$artistInput, $resData] = $this->splitPayload($payload);

            // Wenn Checkbox aktiv: Daten direkt auf Residency speichern, keinen Artist anlegen
            if (!empty($payload['do_not_save_artist'])) {
                $resData['name'] = $artistInput['name'] ?? null;
                $resData['first_name'] = $artistInput['first_name'] ?? null;
                $resData['last_name'] = $artistInput['last_name'] ?? null;
                $resData['phone_number'] = $artistInput['phone_number'] ?? null;
                $resData['position'] = $artistInput['position'] ?? null;
                $resData['do_not_save_artist'] = true;
                $resData['artist_id'] = null;

                return $this->residencies->create($resData);
            }

            $residency = $this->residencies->create($resData);

            // CRM contact: use existing or create new from property values
            $crmContactId = $artistInput['artist_crm_contact_id'] ?? null;
            if (!$crmContactId && !empty($artistInput['crm_property_values'])) {
                $crmContact = $this->createCrmContactForArtist($artistInput['crm_property_values']);
                if ($crmContact) {
                    $crmContactId = $crmContact->id;
                }
            }
            if ($crmContactId) {
                $residency->update(['artist_crm_contact_id' => $crmContactId]);
            }

            // Handle CRM property sync/overrides for existing CRM contacts
            if ($crmContactId && !empty($artistInput['crm_property_values'])) {
                if (!empty($artistInput['sync_crm_changes'])) {
                    $this->syncCrmPropertyValues($crmContactId, $artistInput['crm_property_values']);
                    $residency->update(['crm_property_overrides' => null]);
                } else {
                    $residency->update(['crm_property_overrides' => $artistInput['crm_property_values']]);
                }
            }

            $artist = $this->resolveArtistStrict($artistInput);

            if ($artist instanceof Artist) {
                $this->residencies->associateArtist($residency, $artist);
            }

            return $residency->refresh();
        });
    }

    /** Update + sichere Artist-Verknüpfung/Neuanlage/Dissociate */
    public function update(ArtistResidency $residency, array $payload): ArtistResidency
    {
        return DB::transaction(function () use ($residency, $payload) {
            [$artistInput, $resData] = $this->splitPayload($payload);

            // Wenn do_not_save_artist aktiv: Daten lokal auf Residency speichern, keinen Artist anlegen
            if ($residency->do_not_save_artist) {
                $resData['name'] = $artistInput['name'] ?? $residency->name;
                $resData['first_name'] = $artistInput['first_name'] ?? $residency->first_name;
                $resData['last_name'] = $artistInput['last_name'] ?? $residency->last_name;
                $resData['phone_number'] = $artistInput['phone_number'] ?? $residency->phone_number;
                $resData['position'] = $artistInput['position'] ?? $residency->position;
                $resData['do_not_save_artist'] = true;
                $resData['artist_id'] = null;

                $this->residencies->update($residency, $resData);
                return $residency->refresh();
            }

            if (!empty($resData)) {
                $this->residencies->update($residency, $resData);
            }

            // Handle CRM property sync/overrides
            $crmContactId = $residency->artist_crm_contact_id;
            if ($crmContactId && !empty($artistInput['crm_property_values'])) {
                if (!empty($artistInput['sync_crm_changes'])) {
                    $this->syncCrmPropertyValues($crmContactId, $artistInput['crm_property_values']);
                    $residency->update(['crm_property_overrides' => null]);
                } else {
                    $residency->update(['crm_property_overrides' => $artistInput['crm_property_values']]);
                }
            }

            if (!empty($artistInput) || $this->wantsDissociate($artistInput)) {
                $artist = $this->resolveArtistStrict($artistInput);

                if ($artist instanceof Artist) {
                    $this->residencies->associateArtist($residency, $artist);
                } elseif ($artist === null && $this->wantsDissociate($artistInput)) {
                    $this->residencies->dissociateArtist($residency);
                }
            }

            return $residency->refresh();
        });
    }

    // ---------- Hilfen ----------

    /** Trennt Artist-Felder von Residency-Feldern */
    private function splitPayload(array $payload): array
    {
        $artistKeys = ['artist_id', 'artist_crm_contact_id', 'name', 'first_name', 'last_name', 'phone_number', 'position', 'crm_property_values', 'sync_crm_changes'];
        $artistInput = Arr::only($payload, $artistKeys);
        $residencyData = Arr::except($payload, array_merge($artistKeys, ['do_not_save_artist']));

        return [$artistInput, $residencyData];
    }

    private function wantsDissociate(array $artistInput): bool
    {
        return array_key_exists('artist_id', $artistInput)
            && is_null($artistInput['artist_id'] ?? null)
            && empty(trim((string)($artistInput['name'] ?? '')));
    }

    private function syncCrmPropertyValues(int $contactId, array $propertyValues): void
    {
        foreach ($propertyValues as $propId => $value) {
            if ($value !== null && $value !== '') {
                CrmPropertyValue::updateOrCreate(
                    ['crm_contact_id' => $contactId, 'crm_property_id' => $propId],
                    ['value' => $value]
                );
            } else {
                CrmPropertyValue::where('crm_contact_id', $contactId)
                    ->where('crm_property_id', $propId)
                    ->delete();
            }
        }

        // Update display_name on the CRM contact
        $contact = CrmContact::find($contactId);
        if ($contact) {
            $vornameProp = CrmProperty::where('name', 'Vorname')
                ->whereHas('group', fn ($q) => $q->where('is_system', true))
                ->first();
            $nachnameProp = CrmProperty::where('name', 'Nachname')
                ->whereHas('group', fn ($q) => $q->where('is_system', true))
                ->first();
            $kuenstlerNameProp = CrmProperty::where('name', 'Künstler*innen Name')
                ->whereHas('group', fn ($q) => $q->where('is_system', true))
                ->first();

            $vorname = $propertyValues[$vornameProp?->id] ?? '';
            $nachname = $propertyValues[$nachnameProp?->id] ?? '';
            $kuenstlerName = $propertyValues[$kuenstlerNameProp?->id] ?? '';
            $displayName = trim("$vorname $nachname") ?: $kuenstlerName;

            if (!empty($displayName)) {
                $contact->update(['display_name' => $displayName]);
            }
        }
    }

    private function createCrmContactForArtist(array $propertyValues): ?CrmContact
    {
        $artistType = CrmContactType::where('slug', CrmSystemContactTypeEnum::ARTIST->value)->first();
        if (!$artistType) {
            return null;
        }

        $vornameProp = CrmProperty::where('name', 'Vorname')
            ->whereHas('group', fn ($q) => $q->where('is_system', true))
            ->first();
        $nachnameProp = CrmProperty::where('name', 'Nachname')
            ->whereHas('group', fn ($q) => $q->where('is_system', true))
            ->first();
        $kuenstlerNameProp = CrmProperty::where('name', 'Künstler*innen Name')
            ->whereHas('group', fn ($q) => $q->where('is_system', true))
            ->first();

        $vorname = $propertyValues[$vornameProp?->id] ?? '';
        $nachname = $propertyValues[$nachnameProp?->id] ?? '';
        $kuenstlerName = $propertyValues[$kuenstlerNameProp?->id] ?? '';
        $displayName = trim("$vorname $nachname") ?: $kuenstlerName;

        if (empty($displayName)) {
            return null;
        }

        $contact = CrmContact::create([
            'crm_contact_type_id' => $artistType->id,
            'display_name' => $displayName,
            'is_active' => true,
        ]);

        foreach ($propertyValues as $propId => $value) {
            if ($value !== null && $value !== '') {
                CrmPropertyValue::updateOrCreate(
                    ['crm_contact_id' => $contact->id, 'crm_property_id' => $propId],
                    ['value' => $value]
                );
            }
        }

        return $contact;
    }

    /**
     * Liefert:
     *  - Artist:   wenn verknüpft/gefunden/neu angelegt werden soll
     *  - null:     wenn explizit dissociate gewünscht ist
     *  - wirft ValidationException bei Konflikten oder inkonsistenten Eingaben
     * @throws ValidationException
     */
    private function resolveArtistStrict(array $artistInput): ?Artist
    {
        $hasId   = array_key_exists('artist_id', $artistInput);
        $id      = $artistInput['artist_id'] ?? null;
        $nameRaw = (string)($artistInput['name'] ?? '');
        $name    = trim($nameRaw);

        $extraUpdates = collect($artistInput)
            ->only(['first_name', 'last_name', 'phone_number', 'position'])
            ->filter(fn($v) => !is_null($v) && $v !== '')
            ->all();

        // explizites Dissociate
        if ($this->wantsDissociate($artistInput)) {
            return null;
        }

        // Fall A: ID vorhanden (Name optional)
        if ($hasId && $id) {
            $artist = $this->artists->findById((int)$id, lockForUpdate: true);
            if (!$artist) {
                // Sollte die Request-Rule eigentlich abfangen (exists)
                throw ValidationException::withMessages([
                    'artist_id' => __('Selected artist does not exist.'),
                ]);
            }

            // Konflikterkennung: Name mitgegeben aber passt nicht zum gefundenen Artist
            if ($name !== '') {
                $providedNorm = $this->artists->normalizeName($name);
                $existingNorm = $this->artists->normalizeName($artist->name);
                if ($providedNorm !== $existingNorm) {
                    throw ValidationException::withMessages([
                        'artist_id' => __('Artist conflict: provided name does not match the selected artist.'),
                        'name'      => __('Artist conflict: provided name does not match the selected artist.'),
                    ]);
                }
            }

            // vorsichtig aktualisieren (nur nicht-leere Felder)
            $this->artists->update($artist, $extraUpdates);
            return $artist->refresh();
        }

        // Fall B: keine ID, aber Name vorhanden → finden oder erstellen
        if ($name !== '') {
            // Robust per Lock + Unique-Fallback
            return $this->artists->getOrCreateByNameForUpdate($name, $extraUpdates);
        }

        // Fall C: weder ID noch Name → nichts zu tun (Residency ohne Artist)
        return null;
    }
    /**
     * Exports artist residency data based on project and type.
     */
    public function exportService(Project $project, string $type, string $language): Response|BinaryFileResponse
    {
        $artistResidencies = $this->getArtistResidenciesByProjectId($project->id);

        return match ($type) {
            ExportType::PDF->value => $this->exportToPdf($project, $artistResidencies, $language),
            default => $this->exportToExcel($project, $artistResidencies, $language),
        };
    }

    /**
     * Retrieves artist residencies by project ID.
     */
    public function getArtistResidenciesByProjectId(int $projectId): Collection
    {
        return $this->artistResidencyRepository->getArtistResidencyByProjectId($projectId);
    }

    /**
     * Exports data to PDF format.
     */
    private function exportToPdf(Project $project, Collection $artistResidencies, string $language): Response
    {
        $pdfContent = $this->pdf->loadView(
            'pdf.artist-residency-per-diem',
            [
                'artistResidencies' => $artistResidencies,
                'project' => $project->load(['costCenter']),
                'user' => $this->authManager->user(),
                'language' => $language,
            ]
        )->setPaper('a4', 'landscape')
            ->setOptions([
                'dpi' => 72,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = $this->createFilename(now(), $project->name, '72');
        $filePath = $this->createStoragePath($filename);

        // Ensure the full directory path exists, including any subdirectories from the filename
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdfContent->save($filePath);

        return $this->inertiaResponseFactory->location(
            route('artist-residency.export.pdf.download', ['filename' => $filename])
        );
    }

    /**
     * Exports data to Excel format.
     */
    private function exportToExcel(
        Project $project,
        Collection $artistResidencies,
        string $language
    ): BinaryFileResponse {
        $filename = sprintf(
            'per_diem_export_%s_stand_%s.xlsx',
            $project->name,
            now()->format('d-m-Y_H_i_s')
        );

        return (new ArtistResidencyExcelExport(
            $artistResidencies,
            $project->load(['costCenter']),
            $language
        ))
            ->download($filename)
            ->deleteFileAfterSend();
    }

    /**
     * Exports standalone Per Diem PDF.
     */
    public function exportPerDiemPdf(Project $project, string $language): Response
    {
        $artistResidencies = $this->getArtistResidenciesByProjectId($project->id);
        $generalSettings = app(GeneralSettings::class);

        $bigLogoBase64 = null;
        if ($generalSettings->big_logo_path && Storage::disk('public')->exists($generalSettings->big_logo_path)) {
            $logoPath = Storage::disk('public')->path($generalSettings->big_logo_path);
            $logoContent = file_get_contents($logoPath);
            if ($logoContent !== false) {
                $mimeType = mime_content_type($logoPath) ?: 'image/png';
                $bigLogoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
            }
        }

        $letterhead = [
            'name' => $generalSettings->letterhead_name ?? '',
            'street' => $generalSettings->letterhead_street ?? '',
            'zip_code' => $generalSettings->letterhead_zip_code ?? '',
            'city' => $generalSettings->letterhead_city ?? '',
            'email' => $generalSettings->letterhead_email ?? '',
        ];

        $pdfContent = $this->pdf->loadView(
            'pdf.artist-residency-per-diem-standalone',
            [
                'artistResidencies' => $artistResidencies,
                'project' => $project->load(['costCenter']),
                'language' => $language,
                'bigLogoBase64' => $bigLogoBase64,
                'letterhead' => $letterhead,
            ]
        )->setPaper('a4', 'landscape')
            ->setOptions([
                'dpi' => 72,
                'enable-local-file-access' => true,
            ]);

        $filename = $this->createFilename(now(), $project->name . '_per_diem', '72');
        $filePath = $this->createStoragePath($filename);

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdfContent->save($filePath);

        return $this->inertiaResponseFactory->location(
            route('artist-residency.export.pdf.download', ['filename' => $filename])
        );
    }

    /**
     * Ensures the directory exists, creating it if necessary.
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if ($this->filesystemManager->directoryMissing($directory)) {
            $this->filesystemManager->makeDirectory($directory);
        }
    }

    /**
     * Creates a file path for storage.
     */
    private function createStoragePath(string $filename): string
    {
        return $this->filesystemManager->path('pdf/' . $filename);
    }

    /**
     * Handles PDF file download.
     */
    public function downloadPdf(string $filename): BinaryFileResponse
    {
        return $this->responseFactory->download(
            $this->createStoragePath($filename)
        )->deleteFileAfterSend();
    }

    /**
     * Generates a filename based on the provided parameters.
     */
    public function createFilename(Carbon $carbon, string $title, string $dpi): string
    {
        return sprintf(
            '%s_%s_dpi_%s.pdf',
            $carbon->format('d.m.Y-H:i:s'),
            str_replace(' ', '_', $title),
            $dpi
        );
    }
}
