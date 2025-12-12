<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('inventory_user_filters', function (Blueprint $table) {
            $table->json('tag_ids')->nullable()->after('property_filters');
        });
    }

    public function down()
    {
        Schema::table('inventory_user_filters', function (Blueprint $table) {
            $table->dropColumn('tag_ids');
        });
    }
};
