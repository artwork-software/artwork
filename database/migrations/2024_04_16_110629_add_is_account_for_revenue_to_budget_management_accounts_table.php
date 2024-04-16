<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_management_accounts', function (Blueprint $table) {
            $table->boolean('is_account_for_revenue')->default(false)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('budget_management_accounts', function (Blueprint $table) {
            $table->dropColumn('is_account_for_revenue');
        });
    }
};
