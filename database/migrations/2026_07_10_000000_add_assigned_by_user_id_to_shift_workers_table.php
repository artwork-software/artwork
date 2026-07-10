<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->after('shift_qualification_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_by_user_id');
        });
    }
};
