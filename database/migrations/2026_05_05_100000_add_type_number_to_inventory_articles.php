<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_articles', function (Blueprint $table): void {
            $table->string('type_number', 32)->nullable()->after('id');
            $table->unique('type_number');
        });

        DB::table('inventory_articles')
            ->whereNull('type_number')
            ->orderBy('id')
            ->each(function ($article): void {
                DB::table('inventory_articles')
                    ->where('id', $article->id)
                    ->update([
                        'type_number' => 'aw-' . str_pad((string) $article->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('inventory_articles', function (Blueprint $table): void {
            $table->dropUnique(['type_number']);
            $table->dropColumn('type_number');
        });
    }
};
