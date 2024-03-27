<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('user_vacations')
            ->orderBy('id')->each(function (\stdClass $vacation): void {
                $this->insertToVacation($vacation, \App\Models\User::class);
            });

        DB::table('freelancer_vacations')
            ->orderBy('id')->each(function (\stdClass $vacation): void {
                $this->insertToVacation($vacation, \App\Models\Freelancer::class);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }

    private function insertToVacation(\stdClass $model, string $baseClass): void
    {
        DB::table('vacations')
            ->insert([
                'vacationer_type' => $baseClass,
                'vacationer_id' => $model->id,
                'from' => $model->from,
                'until' => $model->until
            ]);
    }
};
