<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crafts', function (Blueprint $table): void {
            // null = keine Frist-Erinnerung für dieses Gewerk
            $table->unsignedSmallInteger('commit_request_deadline_days')->nullable()->default(14)
                ->after('notify_days');
        });

        Schema::create('shift_plan_request_deadline_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('craft_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['craft_id', 'week_number', 'year'], 'spr_deadline_notifications_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_plan_request_deadline_notifications');

        Schema::table('crafts', function (Blueprint $table): void {
            $table->dropColumn('commit_request_deadline_days');
        });
    }
};
