<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'business')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('business')->nullable()->after('position');
            });
        }

        if (!Schema::hasColumn('freelancers', 'business')) {
            Schema::table('freelancers', function (Blueprint $table) {
                $table->string('business')->nullable()->after('position');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('business');
        });

        Schema::table('freelancers', function (Blueprint $table) {
            $table->dropColumn('business');
        });
    }
};
