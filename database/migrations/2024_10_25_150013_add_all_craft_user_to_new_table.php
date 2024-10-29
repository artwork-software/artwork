<?php

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users_assigned_crafts')
            ->orderBy('id')->each(function (\stdClass $craftable): void {
                $this->insertToCraftable($craftable, User::class, $craftable->user_id);
            });

        DB::table('freelancer_assigned_crafts')
            ->orderBy('id')->each(function (\stdClass $craftable): void {
                $this->insertToCraftable($craftable, Freelancer::class, $craftable->freelancer_id);
            });

        DB::table('service_provider_assigned_crafts')
            ->orderBy('id')->each(function (\stdClass $craftable): void {
                $this->insertToCraftable($craftable, ServiceProvider::class, $craftable->service_provider_id);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }


    private function insertToCraftable(\stdClass $model, string $baseClass, int $modelId): void
    {
        DB::table('craftables')
            ->insert([
                'craft_id' => $model->craft_id,
                'craftable_type' => $baseClass,
                'craftable_id' => $modelId,
            ]);
    }
};
