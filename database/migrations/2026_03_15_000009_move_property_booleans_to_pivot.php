<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add boolean columns to pivot table
        Schema::table('crm_contact_type_property', function (Blueprint $table): void {
            $table->boolean('is_required')->default(false)->after('sort_order');
            $table->boolean('show_in_list')->default(false)->after('is_required');
            $table->boolean('is_filterable')->default(false)->after('show_in_list');
        });

        // 2. Copy existing values from crm_properties into pivot rows
        DB::table('crm_contact_type_property as pivot')
            ->join('crm_properties as p', 'pivot.crm_property_id', '=', 'p.id')
            ->update([
                'pivot.is_required' => DB::raw('p.is_required'),
                'pivot.show_in_list' => DB::raw('p.show_in_list'),
                'pivot.is_filterable' => DB::raw('p.is_filterable'),
            ]);

        // 3. Remove columns from crm_properties
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->dropColumn(['is_required', 'show_in_list', 'is_filterable']);
        });
    }

    public function down(): void
    {
        // 1. Re-add columns to crm_properties
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->boolean('is_required')->default(false)->after('tooltip_text');
            $table->boolean('show_in_list')->default(false)->after('is_required');
            $table->boolean('is_filterable')->default(false)->after('show_in_list');
        });

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
        Schema::table('crm_contact_type_property', function (Blueprint $table): void {
            $table->dropColumn(['is_required', 'show_in_list', 'is_filterable']);
        });
    }
};
