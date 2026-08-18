<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alte Datenbestände enthalten persistierte Avatar-Fallback-URLs in profile_image:
 * - https://ui-avatars.com/... (von der CSP img-src 'self' geblockt → leere Avatar-Kreise)
 * - .../api/generate-avatar-image/... aus der Zeit, als die Route in routes/api.php lag (heute 404)
 * Diese Werte werden genullt; die profile_photo_url-Accessoren generieren dann den lokalen Fallback.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['freelancers', 'service_providers', 'crm_contacts', 'accommodations'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'profile_image')) {
                continue;
            }

            DB::table($table)
                ->where(function ($query): void {
                    $query
                        ->where('profile_image', 'like', '%ui-avatars.com%')
                        ->orWhere('profile_image', 'like', '%generate-avatar-image%');
                })
                ->update(['profile_image' => null]);
        }
    }

    public function down(): void
    {
        // Datenreparatur, nicht umkehrbar
    }
};
