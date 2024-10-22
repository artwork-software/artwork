<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new \Database\Seeders\SubdivisionSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
