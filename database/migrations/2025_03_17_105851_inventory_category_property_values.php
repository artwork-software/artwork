<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_category_property_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_article_property_id');

            // Morph-Spalten (ermöglichen die Zuordnung zu Kategorien oder Subkategorien)
            $table->string('inventory_category_propertyable_type');
            $table->unsignedBigInteger('inventory_category_propertyable_id');

            // Kürzerer Indexname, um MySQL 64-Zeichen-Grenze zu vermeiden
            $table->index(['inventory_category_propertyable_type', 'inventory_category_propertyable_id'], 'inv_cat_prop_morph_idx');

            $table->string('value')->nullable(); // Optionale Vorgabewerte für Properties
            $table->timestamps();

            // Fremdschlüssel-Constraint
            $table->foreign('inventory_article_property_id', 'inv_cat_prop_fk')
                ->references('id')
                ->on('inventory_article_properties')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_category_property_values');
    }
};
