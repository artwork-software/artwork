<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'show_qualification_duplicates')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('show_qualification_duplicates')
                    ->default(true)
                    ->after('closed_qualification_groups');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'show_qualification_duplicates')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('show_qualification_duplicates');
            });
        }
    }
};
