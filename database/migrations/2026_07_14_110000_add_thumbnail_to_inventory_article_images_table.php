<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventory_article_images', 'thumbnail')) {
            return;
        }

        Schema::table('inventory_article_images', function (Blueprint $table): void {
            $table->string('thumbnail')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('inventory_article_images', 'thumbnail')) {
            return;
        }

        Schema::table('inventory_article_images', function (Blueprint $table): void {
            $table->dropColumn('thumbnail');
        });
    }
};
