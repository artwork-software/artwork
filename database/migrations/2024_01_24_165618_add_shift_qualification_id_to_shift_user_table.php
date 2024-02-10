<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('shift_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('shift_qualification_id')->after('user_id');
            $table->foreign('shift_qualification_id')
                ->references('id')
                ->on('shift_qualifications');
            $table->foreign('shift_id', 'shift_user_shift_id_foreign')
                ->references('id')
                ->on('shifts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('shift_user', function (Blueprint $table): void {
            $table->dropForeign('shift_user_shift_qualification_id_foreign');
            $table->dropColumn('shift_qualification_id');
            $table->dropForeign('shift_user_shift_id_foreign');
        });
    }
};
