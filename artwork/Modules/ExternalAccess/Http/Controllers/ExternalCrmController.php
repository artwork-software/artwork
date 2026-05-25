<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExternalCrmController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');
        $contact = $external->crmContact;

        $groups = CrmPropertyGroup::query()
            ->where('is_confidential', false)
            ->whereHas(
                'properties.contactTypes',
                fn ($q) => $q->where('crm_contact_types.id', $contact->crm_contact_type_id),
            )
            ->with([
                'properties' => function ($q) use ($contact): void {
                    $q->whereHas(
                        'contactTypes',
                        fn ($cq) => $cq->where('crm_contact_types.id', $contact->crm_contact_type_id),
                    )
                        ->with([
                            'values' => fn ($vq) => $vq->where('crm_contact_id', $contact->id),
                        ]);
                },
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CrmPropertyGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'properties' => $group->properties->map(fn ($property) => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'value' => $property->values->first()?->value,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Crm/Show', ['groups' => $groups]);
    }
}
