<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;

class ProjectTabArtistResidenciesService
{
    public function buildArtistResidenciesPayload(Project $project): array
    {
        $artistType = CrmContactType::where('slug', CrmSystemContactTypeEnum::ARTIST->value)->first();
        $accommodationType = CrmContactType::where('slug', CrmSystemContactTypeEnum::ACCOMMODATION->value)->first();

        return [
            'artists' => Artist::all(),
            'accommodations' => Accommodation::with('roomTypes')->get(),
            'crm_artists' => $artistType
                ? CrmContact::where('crm_contact_type_id', $artistType->id)
                    ->with('propertyValues')
                    ->get()
                : collect(),
            'artist_contact_type_properties' => $artistType
                ? $artistType->properties()
                    ->with('group')
                    ->orderByPivot('sort_order')
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'type' => $p->type->value,
                        'select_values' => $p->select_values,
                        'group_name' => $p->group->name,
                        'is_required' => (bool) $p->pivot->is_required,
                        'sort_order' => $p->pivot->sort_order,
                    ])
                : collect(),
            'crm_accommodations' => $accommodationType
                ? CrmContact::where('crm_contact_type_id', $accommodationType->id)
                    ->with('roomTypes')
                    ->get()
                : collect(),
            'artist_residencies' => $project->artistResidencies()
                ->with([
                    'accommodation',
                    'accommodation.roomTypes',
                    'artist',
                    'artistContact',
                    'artistContact.propertyValues',
                    'accommodationContact',
                    'accommodationContact.roomTypes',
                    'roomType',
                ])
                ->get(),
            'default_breakfast_deduction' => app(GeneralSettings::class)->breakfast_deduction_per_day,
        ];
    }
}
