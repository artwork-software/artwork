<?php

namespace Artwork\Modules\Permission\Catalog;

/**
 * Typische Rollenbilder eines Hauses. Reine Anzeige-Information ("Typisch für") und Grundlage
 * der ausgelieferten Rechte-Presets (PermissionPresetSeeder). Werte sind Übersetzungsschlüssel.
 */
enum Persona: string
{
    case BASIS = 'Basis (all staff)';
    case PRODUCTION_LEAD = 'Production management';
    case DISPOSITION = 'Disposition / room planning';
    case SHIFT_PLANNING = 'Duty roster planning';
    case CRAFT_LEAD = 'Craft lead';
    case HR = 'HR administration';
    case FINANCE = 'Accounting / controlling';
    case CONTRACTS = 'Contract & document administration';
    case INVENTORY = 'Warehouse / inventory';
    case CRM = 'Artist relations / CRM';
    case SYSADMIN = 'System administration';
}
