<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bi_project_data', function (Blueprint $table) {
            $table->boolean('visitors_not_applicable')->default(false)->after('visitors_total');
            $table->boolean('sold_tickets_not_applicable')->default(false)->after('sold_tickets_total');
            $table->boolean('revenue_not_applicable')->default(false)->after('revenue_total');
        });
    }

    public function down(): void
    {
        Schema::table('bi_project_data', function (Blueprint $table) {
            $table->dropColumn([
                'visitors_not_applicable',
                'sold_tickets_not_applicable',
                'revenue_not_applicable',
            ]);
        });
    }
};
