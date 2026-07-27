<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bi_audience_categories')) {
            Schema::create('bi_audience_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                // Rechenrolle für Quoten (Freikarten-/Ermäßigungsquote), unabhängig vom frei wählbaren Namen.
                $table->enum('pricing_type', ['full', 'reduced', 'free'])->default('full');
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('bi_audience_category_values')) {
            Schema::create('bi_audience_category_values', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bi_audience_category_id')
                    ->constrained('bi_audience_categories')
                    ->cascadeOnDelete();
                // null = Gesamtwert (TOTAL-Modus), sonst Pro-Termin-Wert.
                $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
                // Plan/Ist-Dimension (Phase 3 des BI-Ausbaus); Bestand = Ist.
                $table->enum('scope', ['actual', 'plan'])->default('actual');
                $table->unsignedInteger('quantity')->nullable();
                $table->timestamps();

                $table->unique(
                    ['project_id', 'bi_audience_category_id', 'event_id', 'scope'],
                    'bi_audience_category_values_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_audience_category_values');
        Schema::dropIfExists('bi_audience_categories');
    }
};
