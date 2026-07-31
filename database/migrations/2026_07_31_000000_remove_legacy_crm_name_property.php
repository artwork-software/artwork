<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Frühe Seed-Versionen legten in der System-Gruppe "Basiseigenschaften" eine
// Eigenschaft "Name" an. Der Anzeigename eines Kontakts ist aber die Spalte
// crm_contacts.display_name — die Eigenschaft ist redundant und wird entfernt.
return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('crm_properties')
            || !Schema::hasTable('crm_property_groups')
            || !Schema::hasTable('crm_contacts')
        ) {
            return;
        }

        $group = DB::table('crm_property_groups')
            ->where('name', 'Basiseigenschaften')
            ->where('is_system', true)
            ->first();

        if (!$group) {
            return;
        }

        $property = DB::table('crm_properties')
            ->where('crm_property_group_id', $group->id)
            ->where('name', 'Name')
            ->where('is_system', true)
            ->first();

        if (!$property) {
            return;
        }

        DB::table('crm_property_values')
            ->where('crm_property_id', $property->id)
            ->orderBy('id')
            ->each(function (object $value): void {
                $trimmed = trim((string) $value->value);
                if ($trimmed === '') {
                    return;
                }

                DB::table('crm_contacts')
                    ->where('id', $value->crm_contact_id)
                    ->where(function ($query): void {
                        $query->whereNull('display_name')->orWhere('display_name', '');
                    })
                    ->update(['display_name' => $trimmed]);
            });

        DB::table('crm_property_values')->where('crm_property_id', $property->id)->delete();
        DB::table('crm_contact_type_property')->where('crm_property_id', $property->id)->delete();
        DB::table('crm_properties')->where('id', $property->id)->delete();
    }

    public function down(): void
    {
        // Nicht umkehrbar: die redundante Eigenschaft wird nicht wiederhergestellt.
    }
};
