<?php

use Artwork\Modules\Inventory\Services\TypeNumberGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Widen columns to VARCHAR(64) for ULID format
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->string('type_number', 64)->nullable()->change();
        });

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->string('type_number', 64)->nullable()->change();
        });

        // 2) Migrate existing type numbers to ULID format
        $articles = DB::table('inventory_articles')
            ->whereNotNull('type_number')
            ->get(['id', 'type_number']);

        foreach ($articles as $article) {
            $newTypeNumber = TypeNumberGenerator::generateExternalId();

            DB::table('inventory_articles')
                ->where('id', $article->id)
                ->update(['type_number' => $newTypeNumber]);

            // Update all associated detailed articles
            $detailedArticles = DB::table('inventory_detailed_quantity_articles')
                ->where('inventory_article_id', $article->id)
                ->whereNotNull('type_number')
                ->get(['id', 'detail_number']);

            foreach ($detailedArticles as $detail) {
                $detailTypeNumber = TypeNumberGenerator::generateDetailExternalId(
                    $newTypeNumber,
                    $detail->detail_number
                );

                DB::table('inventory_detailed_quantity_articles')
                    ->where('id', $detail->id)
                    ->update(['type_number' => $detailTypeNumber]);
            }
        }
    }

    public function down(): void
    {
        // Revert column sizes (data cannot be reverted to old format)
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->string('type_number', 32)->nullable()->change();
        });

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->char('type_number', 36)->nullable()->change();
        });
    }
};
