<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ad_managed')->default(false)->after('use_chat');
            $table->string('ad_identifier')->nullable()->after('ad_managed');
            $table->index('ad_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['ad_identifier']);
            $table->dropColumn(['ad_managed', 'ad_identifier']);
        });
    }
};
