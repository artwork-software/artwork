<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Gewählte Ticketprojekt-Gruppen und „online nur im Paket“, bis sie in tickets angekommen sind. */
    public function up(): void
    {
        Schema::table('ticketing_productions', function (Blueprint $table): void {
            $table->json('group_ids')->nullable()->after('reduction_type_ids');
            $table->boolean('package_only')->nullable()->after('group_ids');
        });
    }

    public function down(): void
    {
        Schema::table('ticketing_productions', function (Blueprint $table): void {
            $table->dropColumn(['group_ids', 'package_only']);
        });
    }
};
