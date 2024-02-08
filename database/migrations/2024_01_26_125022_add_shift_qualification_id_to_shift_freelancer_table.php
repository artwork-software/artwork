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
        Schema::table('shifts_freelancers', function (Blueprint $table): void {
            $table->unsignedBigInteger('shift_qualification_id')->after('freelancer_id');
            $table->foreign('shift_qualification_id')
                ->references('id')
                ->on('shift_qualifications');
            $table->foreign('shift_id', 'shift_freelancers_shift_id_foreign')
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
        Schema::table('shifts_freelancers', function (Blueprint $table): void {
            $table->dropForeign('shifts_freelancers_shift_qualification_id_foreign');
            $table->dropColumn('shift_qualification_id');
            $table->dropForeign('shift_freelancers_shift_id_foreign');
        });
    }
};
