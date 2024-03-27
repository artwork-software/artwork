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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('translation_key')->nullable()->after('name');
            $table->string('tooltipKey')->nullable();
        });
        $this->modifyBaseData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            //
        });
    }
};
