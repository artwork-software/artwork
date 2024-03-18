<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Artwork\Modules\Setup\Database\ModifiesBaseData;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->modifyBaseData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
