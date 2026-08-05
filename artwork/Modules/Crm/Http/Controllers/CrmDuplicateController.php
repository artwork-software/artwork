<?php

namespace Artwork\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Services\CrmDuplicateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmDuplicateController extends Controller
{
    private const MIRRORED_SLUGS = [
        CrmSystemContactTypeEnum::USER->value,
        CrmSystemContactTypeEnum::FREELANCER->value,
        CrmSystemContactTypeEnum::SERVICE_PROVIDER->value,
    ];

    public function __construct(
        private readonly CrmDuplicateService $duplicateService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('CRM/Duplicates', [
            'duplicateGroups' => $this->duplicateService->findDuplicateGroups(),
        ]);
    }

    public function merge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_id' => 'required|integer|exists:crm_contacts,id',
            'duplicate_ids' => 'required|array|min:1',
            'duplicate_ids.*' => 'integer|exists:crm_contacts,id|different:primary_id',
        ]);

        $primary = CrmContact::with('contactType')->findOrFail($validated['primary_id']);
        $duplicates = CrmContact::whereIn('id', $validated['duplicate_ids'])->get();

        if (in_array($primary->contactType?->slug, self::MIRRORED_SLUGS, true)) {
            abort(422, 'Gespiegelte Kontakte können nicht zusammengeführt werden.');
        }

        foreach ($duplicates as $duplicate) {
            if ($duplicate->crm_contact_type_id !== $primary->crm_contact_type_id) {
                abort(422, 'Nur Kontakte desselben Typs können zusammengeführt werden.');
            }

            // Entity-gebundene Kontakte (User, Künstler*innen …) dürfen nur Hauptkontakt sein
            if ($duplicate->entity_type !== null) {
                abort(422, 'Verknüpfte Kontakte können nur als Hauptkontakt gewählt werden.');
            }
        }

        $this->duplicateService->merge($primary, $duplicates);

        return redirect()->back()->with(
            'success',
            __(':count contacts merged into ":name".', [
                'count' => $duplicates->count(),
                'name' => $primary->display_name,
            ])
        );
    }
}
