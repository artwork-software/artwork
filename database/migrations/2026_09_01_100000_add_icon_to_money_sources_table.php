<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('money_sources', function (Blueprint $table): void {
            // Tabler-Export-Name (z.B. "IconHome2"), nur für Gruppen genutzt
            $table->string('icon')->nullable()->after('is_group');
        });
    }

    public function down(): void
    {
        Schema::table('money_sources', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
    }
};
