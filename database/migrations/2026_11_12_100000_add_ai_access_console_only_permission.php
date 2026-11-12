<?php

use Artwork\Modules\Permission\Enums\ConsoleOnlyPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt das nur per Tinker vergebbare Recht "ai access" an (ConsoleOnlyPermissionEnum).
 *
 * Bewusst nicht im Rechte-Katalog: Seeder und artwork:update-permissions kennen es nicht, die
 * Rechteseite zeigt es nicht an. Diese Migration ist deshalb auch für Neuinstallationen die
 * einzige Quelle der Zeile.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (ConsoleOnlyPermissionEnum::values() as $name) {
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => 'web',
                'translation_key' => null,
                'group' => null,
                'tooltipKey' => null,
                'checked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Bewusst leer: das Recht wurde gezielt per Konsole vergeben und soll bei einem Rollback
        // nicht still mitsamt aller Zuordnungen verschwinden.
    }
};
