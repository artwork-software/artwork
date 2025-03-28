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
        Schema::create('inventory_property_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_article_property_id');

            // Ersetzt $table->morphs('inventory_propertyable') durch manuelle Definition:
            $table->string('inventory_propertyable_type');
            $table->unsignedBigInteger('inventory_propertyable_id');

            // Manuell Index mit kürzerem Namen setzen
            $table->index(['inventory_propertyable_type', 'inventory_propertyable_id'], 'inv_prop_val_morph_idx');

            $table->string('value');
            $table->timestamps();

            // Fremdschlüssel für die Property
            $table->foreign('inventory_article_property_id', 'inv_art_prop_fk')
                ->references('id')
                ->on('inventory_article_properties')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_property_values');
    }
};
