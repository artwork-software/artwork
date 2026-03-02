<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('gema')->default(false)->after('cost_center_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'own_copyright')) {
                $table->dropColumn('own_copyright');
            }
            if (Schema::hasColumn('projects', 'live_music')) {
                $table->dropColumn('live_music');
            }
            if (Schema::hasColumn('projects', 'law_size')) {
                $table->dropColumn('law_size');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'collecting_society_id')) {
                try {
                    $table->dropForeign(['collecting_society_id']);
                } catch (\Exception $e) {
                    // No foreign key exists, continue
                }
                $table->dropColumn('collecting_society_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'gema')) {
                $table->dropColumn('gema');
            }
            $table->boolean('own_copyright')->default(false);
            $table->boolean('live_music')->default(false);
            $table->string('law_size')->nullable();
            $table->unsignedBigInteger('collecting_society_id')->nullable();
        });
    }
};
