<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        /**
*'own_copyright',
         * 'live_music',
         * 'collecting_society_id',
         * 'law_size',
         */
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignId('cost_center_id')->nullable()->after('closed_society')
                ->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('collecting_society_id')->nullable()->after('closed_society')
                ->constrained('collecting_societies')->nullOnDelete();
            $table->boolean('own_copyright')->default(false)->after('closed_society');
            $table->boolean('live_music')->default(false)->after('closed_society');
            $table->enum('law_size', ['SMALL', 'BIG'])->default('SMALL')->after('closed_society');
            $table->string('cost_center_description')->nullable()->after('cost_center_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['collecting_society_id']);
            $table->dropColumn('cost_center_id');
            $table->dropColumn('collecting_society_id');
            $table->dropColumn('own_copyright');
            $table->dropColumn('live_music');
            $table->dropColumn('law_size');
            $table->dropColumn('cost_center_description');
        });
    }
};
