<?php

use Artwork\Modules\Project\TabTemplates\ProductionInquiryTemplateUpgrade;
use Illuminate\Database\Migrations\Migration;

/**
 * Tabs aus der Erstfassung der Vorlage „Abfrage Produktion“, die nicht umgebaut wurden, bekommen die
 * aktuelle Fassung (Abschnittsbalken, sichtbare Hinweise, CRM-Kontaktliste). Erfasste Werte bleiben.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(ProductionInquiryTemplateUpgrade::class)->run();
    }

    public function down(): void
    {
        // Keine Rückumstellung.
    }
};
