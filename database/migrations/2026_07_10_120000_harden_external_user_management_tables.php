<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_user_sources', function (Blueprint $table): void {
            $table->longText('config')->nullable()->change();
        });

        DB::table('external_user_sources')
            ->select(['id', 'config'])
            ->whereNotNull('config')
            ->orderBy('id')
            ->chunkById(100, function ($sources): void {
                foreach ($sources as $source) {
                    try {
                        Crypt::decryptString($source->config);
                    } catch (DecryptException) {
                        DB::table('external_user_sources')
                            ->where('id', $source->id)
                            ->update(['config' => Crypt::encryptString($source->config)]);
                    }
                }
            });

        Schema::table('external_users', function (Blueprint $table): void {
            $table->unique(
                ['source_id', 'identification'],
                'external_users_source_identification_unique'
            );
            $table->index('user_id', 'external_users_user_id_index');
        });

        Schema::table('external_user_group_mappings', function (Blueprint $table): void {
            $table->index('source_id', 'external_user_group_mappings_source_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('external_user_group_mappings', function (Blueprint $table): void {
            $table->dropIndex('external_user_group_mappings_source_id_index');
        });

        Schema::table('external_users', function (Blueprint $table): void {
            $table->dropUnique('external_users_source_identification_unique');
            $table->dropIndex('external_users_user_id_index');
        });

        DB::table('external_user_sources')
            ->select(['id', 'config'])
            ->whereNotNull('config')
            ->orderBy('id')
            ->chunkById(100, function ($sources): void {
                foreach ($sources as $source) {
                    DB::table('external_user_sources')
                        ->where('id', $source->id)
                        ->update(['config' => Crypt::decryptString($source->config)]);
                }
            });

        Schema::table('external_user_sources', function (Blueprint $table): void {
            $table->json('config')->nullable()->change();
        });
    }
};
