<?php

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove "Name" property from all contact type assignments (pivot table)
        $nameProperty = CrmProperty::where('name', 'Name')
            ->whereHas('group', fn ($q) => $q->where('name', 'Basiseigenschaften'))
            ->first();

        if ($nameProperty) {
            DB::table('crm_contact_type_property')
                ->where('crm_property_id', $nameProperty->id)
                ->delete();
        }

        // 2. Assign "Email" to all contact types that don't have it yet
        $emailProperty = CrmProperty::where('name', 'Email')
            ->whereHas('group', fn ($q) => $q->where('name', 'Basiseigenschaften'))
            ->first();

        if ($emailProperty) {
            $allTypes = CrmContactType::all();

            foreach ($allTypes as $type) {
                $exists = DB::table('crm_contact_type_property')
                    ->where('crm_contact_type_id', $type->id)
                    ->where('crm_property_id', $emailProperty->id)
                    ->exists();

                if (!$exists) {
                    $maxSort = DB::table('crm_contact_type_property')
                        ->where('crm_contact_type_id', $type->id)
                        ->max('sort_order') ?? 0;

                    DB::table('crm_contact_type_property')->insert([
                        'crm_contact_type_id' => $type->id,
                        'crm_property_id' => $emailProperty->id,
                        'sort_order' => $maxSort + 1,
                        'is_required' => false,
                        'show_in_list' => true,
                        'is_filterable' => false,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Not reversible in a meaningful way
    }
};
