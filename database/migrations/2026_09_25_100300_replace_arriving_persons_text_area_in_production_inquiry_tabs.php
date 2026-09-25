<?php

use Artwork\Modules\Project\TabTemplates\ProductionInquiryArrivingPersonsUpgrade;
use Illuminate\Database\Migrations\Migration;

/**
 * Bestehende Tabs aus der Vorlage „Abfrage Produktion“: „Anreisende Personen“ wird zur CRM-Kontaktliste.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(ProductionInquiryArrivingPersonsUpgrade::class)->run();
    }

    public function down(): void
    {
        // Keine Rückumstellung: angelegte Kontakte hängen an der neuen Komponente.
    }
};
