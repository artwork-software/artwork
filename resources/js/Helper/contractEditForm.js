import { toYmd } from './IsoWeek.js';
import { sharedDepartmentsOf, sharedUsersOf } from './sharedAccess.js';

/**
 * Felder, die ContractEditModal vorbelegt und beim Speichern vollständig zurückschickt. Das Backend
 * übernimmt sie per fill() (ContractService::updateContract) – fehlt ein Schlüssel in der Quelle, sendet
 * das Modal einen leeren Wert und überschreibt den gespeicherten.
 */
export const CONTRACT_EDIT_FIELDS = [
    'name',
    'partner',
    'project',
    'description',
    'company_type',
    'contract_type',
    'currency',
    'amount',
    'ksk_liable',
    'ksk_amount',
    'ksk_reason',
    'resident_abroad',
    'foreign_tax',
    'foreign_tax_amount',
    'foreign_tax_city',
    'foreign_tax_country',
    'foreign_tax_reason',
    'contract_state',
    'contract_state_comment',
    'reverse_charge_amount',
    'deadline_date',
    'has_power_of_attorney',
    'is_freed',
    'accessibleUsers',
    'accessibleDepartments',
];

/**
 * Kalenderdatum für <input type="date"> (Y-m-d). Reines Y-m-d bleibt unverändert; Zeitpunkte (z. B. das
 * frühere "2026-10-05T22:00:00.000000Z" für den 06.10. in Europe/Berlin) werden in lokaler Zeit gelesen –
 * toISOString() lieferte hier den Vortag, jedes Speichern verschob die Frist um einen Tag.
 *
 * @param {string|Date|null|undefined} value
 * @returns {string|null}
 */
export function toDateInputValue(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const text = value instanceof Date ? null : String(value);
    if (text !== null && /^\d{4}-\d{2}-\d{2}$/.test(text)) {
        return text;
    }

    const date = value instanceof Date ? value : new Date(text);

    return Number.isNaN(date.getTime()) ? null : toYmd(date);
}

function resolveById(current, id, list) {
    if (current) {
        return current;
    }
    if (id === null || id === undefined || id === '') {
        return null;
    }

    // Nie still auf einen Default fallen, wenn eine Id vorhanden ist: notfalls nur mit Id weitergeben
    return (Array.isArray(list) ? list.find((entry) => Number(entry?.id) === Number(id)) : null) ?? { id };
}

/**
 * Vereinheitlicht die drei Vertragsformen, mit denen ContractEditModal geöffnet wird:
 * - ContractResource (Vertragsübersicht): partner, project, accessibleUsers …
 * - ProjectController (Verträge & Dokumente): wie oben, project nur {id, name}
 * - rohes Eloquent-Modell (Budget-Informationen): contract_partner, project_id, accessing_users …
 * Typ, Rechtsform und Währung werden aus *_id und den Auswahllisten aufgelöst, falls die Relation fehlt.
 *
 * @param {object|null|undefined} contract
 * @param {{ id: number, name?: string }|null} [fallbackProject] Projekt der Aufrufstelle, falls der
 *        Vertrag nur project_id trägt (Name für die Anzeige im Modal)
 * @param {{ currencies?: Array, companyTypes?: Array, contractTypes?: Array }} [lookups] Auswahllisten
 * @returns {object|null}
 */
export function normalizeContractForEdit(contract, fallbackProject = null, lookups = {}) {
    if (!contract) {
        return null;
    }

    const projectId = contract.project?.id ?? contract.project_id ?? null;
    let project = contract.project ?? null;
    if (project === null && projectId !== null) {
        project = Number(fallbackProject?.id) === Number(projectId)
            ? { id: projectId, name: fallbackProject.name }
            : { id: projectId };
    }

    return {
        ...contract,
        partner: contract.partner ?? contract.contract_partner ?? '',
        project,
        foreign_tax: contract.foreign_tax ?? contract.has_foreign_tax ?? false,
        currency: resolveById(contract.currency, contract.currency_id, lookups.currencies),
        company_type: resolveById(contract.company_type, contract.company_type_id, lookups.companyTypes),
        contract_type: resolveById(contract.contract_type, contract.contract_type_id, lookups.contractTypes),
        deadline_date: toDateInputValue(contract.deadline_date),
        accessibleUsers: sharedUsersOf(contract),
        accessibleDepartments: sharedDepartmentsOf(contract),
    };
}
