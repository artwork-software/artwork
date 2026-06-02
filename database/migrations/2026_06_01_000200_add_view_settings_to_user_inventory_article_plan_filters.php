<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ref 1.18: persist the article-planning view settings per user
     * (only-planned toggle + expanded categories/subcategories). Grouping is
     * collapsed by default; the expanded sets are stored explicitly.
     */
    public function up(): void
    {
        Schema::table('user_inventory_article_plan_filters', function (Blueprint $table) {
            $table->boolean('only_planned')->default(false)->after('end_date');
            $table->json('open_categories')->nullable()->after('only_planned');
            $table->json('open_subcategories')->nullable()->after('open_categories');
        });
    }

    public function down(): void
    {
        Schema::table('user_inventory_article_plan_filters', function (Blueprint $table) {
            $table->dropColumn(['only_planned', 'open_categories', 'open_subcategories']);
        });
    }
};
