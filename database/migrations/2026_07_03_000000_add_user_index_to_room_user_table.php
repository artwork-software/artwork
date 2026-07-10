<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_user', function (Blueprint $table): void {
            $table->index(['user_id', 'is_admin'], 'room_user_user_id_is_admin_index');
        });
    }

    public function down(): void
    {
        Schema::table('room_user', function (Blueprint $table): void {
            $table->dropIndex('room_user_user_id_is_admin_index');
        });
    }
};
