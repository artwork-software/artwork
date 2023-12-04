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
    public function up()
    {
        \Illuminate\Support\Facades\DB::table('user_vacations')
            ->orderBy('id')->each(function(\stdClass $vacation) {
                $this->insertToVacation($vacation, \App\Models\User::class);
            });

        \Illuminate\Support\Facades\DB::table('freelancer_vacations')
            ->orderBy('id')->each(function(\stdClass $vacation) {
                $this->insertToVacation($vacation, \App\Models\Freelancer::class);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

    private function insertToVacation(\stdClass $model, string $baseClass): void
    {
        \Illuminate\Support\Facades\DB::table('vacations')
            ->insert([
                'vacationer_type' => $baseClass,
                'vacationer_id' => $model->id,
                'from' => $model->from,
                'until' => $model->until
            ]);
    }
};
