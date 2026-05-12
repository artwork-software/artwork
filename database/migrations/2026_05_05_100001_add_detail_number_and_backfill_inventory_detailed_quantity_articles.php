<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table): void {
            $table->unsignedInteger('detail_number')->nullable()->after('inventory_article_id');
        });

        $articleIds = DB::table('inventory_detailed_quantity_articles')
            ->select('inventory_article_id')
            ->distinct()
            ->pluck('inventory_article_id');

        foreach ($articleIds as $articleId) {
            $details = DB::table('inventory_detailed_quantity_articles')
                ->where('inventory_article_id', $articleId)
                ->orderBy('id')
                ->get(['id']);

            $paddedArticleId = str_pad((string) $articleId, 6, '0', STR_PAD_LEFT);
            $counter = 1;

            foreach ($details as $detail) {
                DB::table('inventory_detailed_quantity_articles')
                    ->where('id', $detail->id)
                    ->update([
                        'detail_number' => $counter,
                        'type_number' => 'aw-' . $paddedArticleId . '-' . $counter,
                    ]);

                $counter++;
            }
        }

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table): void {
            $table->unique(['inventory_article_id', 'detail_number'], 'idqa_article_detail_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table): void {
            $table->dropUnique('idqa_article_detail_number_unique');
            $table->dropColumn('detail_number');
        });
    }
};
