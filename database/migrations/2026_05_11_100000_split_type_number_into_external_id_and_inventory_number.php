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
        // ── inventory_articles ──────────────────────────────────────────

        // 1) Add new columns (nullable initially)
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable()->after('id');
            $table->string('inventory_number', 32)->nullable()->after('external_id');
        });

        // 2) Copy type_number → external_id
        DB::table('inventory_articles')->update([
            'external_id' => DB::raw('type_number'),
        ]);

        // 3) Generate inventory_numbers sequentially by created_at
        $articles = DB::table('inventory_articles')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id');

        foreach ($articles as $index => $id) {
            DB::table('inventory_articles')
                ->where('id', $id)
                ->update([
                    'inventory_number' => str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                ]);
        }

        // 4) Set NOT NULL + unique indexes
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable(false)->change();
            $table->string('inventory_number', 32)->nullable(false)->change();
            $table->unique('external_id');
            $table->unique('inventory_number');
        });

        // 5) Drop old type_number column (remove unique index first)
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->dropUnique('inventory_articles_type_number_unique');
            $table->dropColumn('type_number');
        });

        // ── inventory_detailed_quantity_articles ─────────────────────────

        // 1) Add new columns (nullable initially)
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable()->after('id');
            $table->string('inventory_number', 32)->nullable()->after('external_id');
        });

        // 2) Copy type_number → external_id
        DB::table('inventory_detailed_quantity_articles')
            ->whereNotNull('type_number')
            ->update([
                'external_id' => DB::raw('type_number'),
            ]);

        // 3) Generate inventory_numbers from parent + detail_number
        $details = DB::table('inventory_detailed_quantity_articles as d')
            ->join('inventory_articles as a', 'a.id', '=', 'd.inventory_article_id')
            ->select(['d.id', 'a.inventory_number as main_inv_number', 'd.detail_number'])
            ->get();

        foreach ($details as $detail) {
            DB::table('inventory_detailed_quantity_articles')
                ->where('id', $detail->id)
                ->update([
                    'inventory_number' => $detail->main_inv_number
                        . '-'
                        . str_pad((string) $detail->detail_number, 3, '0', STR_PAD_LEFT),
                ]);
        }

        // 4) Set NOT NULL + unique indexes
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable(false)->change();
            $table->string('inventory_number', 32)->nullable(false)->change();
            $table->unique('external_id');
            $table->unique('inventory_number');
        });

        // 5) Drop old type_number column
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->dropUnique('inventory_detailed_quantity_articles_type_number_unique');
            $table->dropColumn('type_number');
        });
    }

    public function down(): void
    {
        // ── inventory_detailed_quantity_articles ─────────────────────────
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->string('type_number', 64)->nullable()->after('id');
        });

        DB::table('inventory_detailed_quantity_articles')->update([
            'type_number' => DB::raw('external_id'),
        ]);

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->unique('type_number', 'inventory_detailed_quantity_articles_type_number_unique');
            $table->dropUnique(['external_id']);
            $table->dropUnique(['inventory_number']);
            $table->dropColumn(['external_id', 'inventory_number']);
        });

        // ── inventory_articles ──────────────────────────────────────────
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->string('type_number', 64)->nullable()->after('id');
        });

        DB::table('inventory_articles')->update([
            'type_number' => DB::raw('external_id'),
        ]);

        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->unique('type_number', 'inventory_articles_type_number_unique');
            $table->dropUnique(['external_id']);
            $table->dropUnique(['inventory_number']);
            $table->dropColumn(['external_id', 'inventory_number']);
        });
    }
};
