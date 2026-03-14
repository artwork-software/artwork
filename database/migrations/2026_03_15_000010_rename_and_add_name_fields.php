<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. artists: civil_name -> first_name, add last_name
        if (Schema::hasTable('artists')) {
            if (Schema::hasColumn('artists', 'civil_name')) {
                Schema::table('artists', function (Blueprint $table) {
                    $table->renameColumn('civil_name', 'first_name');
                });
            }
            if (!Schema::hasColumn('artists', 'last_name')) {
                Schema::table('artists', function (Blueprint $table) {
                    $table->string('last_name')->nullable()->after('first_name');
                });
            }
        }

        // 2. artist_residencies: civil_name -> first_name, add last_name
        if (Schema::hasTable('artist_residencies')) {
            if (Schema::hasColumn('artist_residencies', 'civil_name')) {
                Schema::table('artist_residencies', function (Blueprint $table) {
                    $table->renameColumn('civil_name', 'first_name');
                });
            }
            if (!Schema::hasColumn('artist_residencies', 'last_name')) {
                Schema::table('artist_residencies', function (Blueprint $table) {
                    $table->string('last_name')->nullable()->after('first_name');
                });
            }
        }

        // 3. CRM Property changes
        if (!Schema::hasTable('crm_properties') || !Schema::hasTable('crm_property_groups')) {
            return;
        }

        $baseGroupId = DB::table('crm_property_groups')
            ->where('name', 'Basiseigenschaften')
            ->where('is_system', true)
            ->value('id');

        if (!$baseGroupId) {
            return;
        }

        // 3a. Rename "Bürgerlicher Name" -> "Vorname" (global rename, only assigned to Artist type)
        DB::table('crm_properties')
            ->where('name', 'Bürgerlicher Name')
            ->where('crm_property_group_id', $baseGroupId)
            ->update(['name' => 'Vorname']);

        // 3b. Create new property "Künstler*innen Name"
        $namePropertyId = DB::table('crm_properties')
            ->where('name', 'Name')
            ->where('crm_property_group_id', $baseGroupId)
            ->value('id');

        $kuenstlerNameId = DB::table('crm_properties')->insertGetId([
            'crm_property_group_id' => $baseGroupId,
            'name' => 'Künstler*innen Name',
            'type' => 'text',
            'is_system' => true,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3c. Create new property "Nachname"
        $nachnameId = DB::table('crm_properties')->insertGetId([
            'crm_property_group_id' => $baseGroupId,
            'name' => 'Nachname',
            'type' => 'text',
            'is_system' => true,
            'sort_order' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3d. Artist contact type: replace "Name" with "Künstler*innen Name" in pivot
        $artistTypeId = DB::table('crm_contact_types')
            ->where('slug', 'artist')
            ->value('id');

        if ($artistTypeId && $namePropertyId) {
            $existingPivot = DB::table('crm_contact_type_property')
                ->where('crm_contact_type_id', $artistTypeId)
                ->where('crm_property_id', $namePropertyId)
                ->first();

            if ($existingPivot) {
                // Remove "Name" from Artist type
                DB::table('crm_contact_type_property')
                    ->where('crm_contact_type_id', $artistTypeId)
                    ->where('crm_property_id', $namePropertyId)
                    ->delete();

                // Add "Künstler*innen Name" to Artist type
                DB::table('crm_contact_type_property')->insert([
                    'crm_contact_type_id' => $artistTypeId,
                    'crm_property_id' => $kuenstlerNameId,
                    'sort_order' => $existingPivot->sort_order ?? 0,
                    'is_required' => $existingPivot->is_required ?? false,
                    'show_in_list' => $existingPivot->show_in_list ?? true,
                    'is_filterable' => $existingPivot->is_filterable ?? false,
                ]);
            }

            // Add "Nachname" to Artist type
            DB::table('crm_contact_type_property')->insertOrIgnore([
                'crm_contact_type_id' => $artistTypeId,
                'crm_property_id' => $nachnameId,
                'sort_order' => 3,
                'is_required' => false,
                'show_in_list' => false,
                'is_filterable' => false,
            ]);

            // 3e. Migrate PropertyValues: for Artist contacts, "Name" -> "Künstler*innen Name"
            $artistContactIds = DB::table('crm_contacts')
                ->where('crm_contact_type_id', $artistTypeId)
                ->pluck('id');

            if ($artistContactIds->isNotEmpty()) {
                DB::table('crm_property_values')
                    ->whereIn('crm_contact_id', $artistContactIds)
                    ->where('crm_property_id', $namePropertyId)
                    ->update(['crm_property_id' => $kuenstlerNameId]);
            }
        }
    }

    public function down(): void
    {
        // Reverse CRM property changes
        if (Schema::hasTable('crm_properties') && Schema::hasTable('crm_property_groups')) {
            $baseGroupId = DB::table('crm_property_groups')
                ->where('name', 'Basiseigenschaften')
                ->where('is_system', true)
                ->value('id');

            if ($baseGroupId) {
                $artistTypeId = DB::table('crm_contact_types')
                    ->where('slug', 'artist')
                    ->value('id');

                $kuenstlerNameId = DB::table('crm_properties')
                    ->where('name', 'Künstler*innen Name')
                    ->where('crm_property_group_id', $baseGroupId)
                    ->value('id');

                $namePropertyId = DB::table('crm_properties')
                    ->where('name', 'Name')
                    ->where('crm_property_group_id', $baseGroupId)
                    ->value('id');

                // Restore Artist pivot: "Künstler*innen Name" -> "Name"
                if ($artistTypeId && $kuenstlerNameId && $namePropertyId) {
                    $artistContactIds = DB::table('crm_contacts')
                        ->where('crm_contact_type_id', $artistTypeId)
                        ->pluck('id');

                    if ($artistContactIds->isNotEmpty()) {
                        DB::table('crm_property_values')
                            ->whereIn('crm_contact_id', $artistContactIds)
                            ->where('crm_property_id', $kuenstlerNameId)
                            ->update(['crm_property_id' => $namePropertyId]);
                    }

                    DB::table('crm_contact_type_property')
                        ->where('crm_contact_type_id', $artistTypeId)
                        ->where('crm_property_id', $kuenstlerNameId)
                        ->delete();

                    DB::table('crm_contact_type_property')->insertOrIgnore([
                        'crm_contact_type_id' => $artistTypeId,
                        'crm_property_id' => $namePropertyId,
                        'sort_order' => 0,
                        'is_required' => true,
                        'show_in_list' => true,
                        'is_filterable' => false,
                    ]);
                }

                // Delete "Nachname"
                $nachnameId = DB::table('crm_properties')
                    ->where('name', 'Nachname')
                    ->where('crm_property_group_id', $baseGroupId)
                    ->value('id');
                if ($nachnameId) {
                    DB::table('crm_property_values')->where('crm_property_id', $nachnameId)->delete();
                    DB::table('crm_contact_type_property')->where('crm_property_id', $nachnameId)->delete();
                    DB::table('crm_properties')->where('id', $nachnameId)->delete();
                }

                // Delete "Künstler*innen Name"
                if ($kuenstlerNameId) {
                    DB::table('crm_property_values')->where('crm_property_id', $kuenstlerNameId)->delete();
                    DB::table('crm_contact_type_property')->where('crm_property_id', $kuenstlerNameId)->delete();
                    DB::table('crm_properties')->where('id', $kuenstlerNameId)->delete();
                }

                // Rename "Vorname" -> "Bürgerlicher Name"
                DB::table('crm_properties')
                    ->where('name', 'Vorname')
                    ->where('crm_property_group_id', $baseGroupId)
                    ->update(['name' => 'Bürgerlicher Name']);
            }
        }

        // Reverse column changes
        if (Schema::hasTable('artist_residencies')) {
            if (Schema::hasColumn('artist_residencies', 'last_name')) {
                Schema::table('artist_residencies', function (Blueprint $table) {
                    $table->dropColumn('last_name');
                });
            }
            if (Schema::hasColumn('artist_residencies', 'first_name')) {
                Schema::table('artist_residencies', function (Blueprint $table) {
                    $table->renameColumn('first_name', 'civil_name');
                });
            }
        }

        if (Schema::hasTable('artists')) {
            if (Schema::hasColumn('artists', 'last_name')) {
                Schema::table('artists', function (Blueprint $table) {
                    $table->dropColumn('last_name');
                });
            }
            if (Schema::hasColumn('artists', 'first_name')) {
                Schema::table('artists', function (Blueprint $table) {
                    $table->renameColumn('first_name', 'civil_name');
                });
            }
        }
    }
};
