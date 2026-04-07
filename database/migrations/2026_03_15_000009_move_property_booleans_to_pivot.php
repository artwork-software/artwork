<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add boolean columns to pivot table (if not already present)
        if (!Schema::hasColumn('crm_contact_type_property', 'is_required')) {
            Schema::table('crm_contact_type_property', function (Blueprint $table): void {
                $table->boolean('is_required')->default(false)->after('sort_order');
                $table->boolean('show_in_list')->default(false)->after('is_required');
                $table->boolean('is_filterable')->default(false)->after('show_in_list');
            });
        }

        // 2. Copy existing values from crm_properties into pivot rows (only if source columns still exist)
        if (Schema::hasColumn('crm_properties', 'is_required')) {
            DB::statement('
                UPDATE crm_contact_type_property
                INNER JOIN crm_properties ON crm_contact_type_property.crm_property_id = crm_properties.id
                SET crm_contact_type_property.is_required = crm_properties.is_required,
                    crm_contact_type_property.show_in_list = crm_properties.show_in_list,
                    crm_contact_type_property.is_filterable = crm_properties.is_filterable
            ');

            // 3. Remove columns from crm_properties
            Schema::table('crm_properties', function (Blueprint $table): void {
                $table->dropColumn(['is_required', 'show_in_list', 'is_filterable']);
            });
        }
    }

    public function down(): void
    {
        // 1. Re-add columns to crm_properties
        if (!Schema::hasColumn('crm_properties', 'is_required')) {
            Schema::table('crm_properties', function (Blueprint $table): void {
                $table->boolean('is_required')->default(false)->after('tooltip_text');
                $table->boolean('show_in_list')->default(false)->after('is_required');
                $table->boolean('is_filterable')->default(false)->after('show_in_list');
            });
        }

        // 2. Copy values back (take first pivot row's values)
        $pivotRows = DB::table('crm_contact_type_property')
            ->select('crm_property_id', 'is_required', 'show_in_list', 'is_filterable')
            ->get()
            ->groupBy('crm_property_id');

        foreach ($pivotRows as $propertyId => $rows) {
            $first = $rows->first();
            DB::table('crm_properties')
                ->where('id', $propertyId)
                ->update([
                    'is_required' => $first->is_required,
                    'show_in_list' => $first->show_in_list,
                    'is_filterable' => $first->is_filterable,
                ]);
        }

        // 3. Remove columns from pivot
        if (Schema::hasColumn('crm_contact_type_property', 'is_required')) {
            Schema::table('crm_contact_type_property', function (Blueprint $table): void {
                $table->dropColumn(['is_required', 'show_in_list', 'is_filterable']);
            });
        }
    }
};
