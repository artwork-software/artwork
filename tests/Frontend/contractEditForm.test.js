import test from 'node:test';
import assert from 'node:assert/strict';
import {
    CONTRACT_EDIT_FIELDS,
    normalizeContractForEdit,
    toDateInputValue,
} from '../../resources/js/Helper/contractEditForm.js';

// Der Fristen-Versatz tritt nur östlich von UTC auf – so laufen die Tests wie bei den Nutzer*innen
process.env.TZ = 'Europe/Berlin';

const common = {
    id: 4,
    name: 'vertrag.pdf',
    description: 'Gastspiel',
    company_type: { id: 2, name: 'GmbH' },
    contract_type: { id: 3, name: 'Gastspielvertrag' },
    currency: { id: 1, name: '€' },
    amount: 0,
    ksk_liable: true,
    ksk_amount: '12.50',
    ksk_reason: 'Künstler',
    resident_abroad: true,
    foreign_tax_amount: '100.00',
    foreign_tax_city: 'Wien',
    foreign_tax_country: 'AT',
    foreign_tax_reason: 'Quellensteuer',
    contract_state: 'signed',
    contract_state_comment: 'liegt vor',
    reverse_charge_amount: '5.00',
    deadline_date: '2026-10-06',
    has_power_of_attorney: false,
    is_freed: false,
};

// Budget-Informationen: rohes Eloquent-Modell (Spaltennamen, Relationen snake_case)
const rawModel = {
    ...common,
    contract_partner: 'Agentur XYZ',
    project_id: 9,
    foreign_tax: true,
    accessing_users: [{ id: 5 }],
    accessing_departments: [{ id: 6 }],
};

// Verträge & Dokumente (ProjectController::getContractsDocumentsTabInertiaData)
const projectTabRow = {
    ...common,
    partner: 'Agentur XYZ',
    project: { id: 9, name: 'Hamlet' },
    has_foreign_tax: true,
    foreign_tax: true,
    accessibleUsers: [{ id: 5 }],
    accessibleDepartments: [{ id: 6 }],
};

// Vertragsübersicht (ContractResource): displayedAccessUsers enthält zusätzlich Projektleitungen (nur Anzeige)
const contractResource = {
    ...common,
    partner: 'Agentur XYZ',
    project: { id: 9, name: 'Hamlet', calendar_tab_id: 2 },
    foreign_tax: true,
    accessibleUsers: [{ id: 5 }],
    displayedAccessUsers: [{ id: 5 }, { id: 99 }],
    accessibleDepartments: [{ id: 6 }],
};

for (const [shape, contract] of Object.entries({ rawModel, projectTabRow, contractResource })) {
    test(`${shape}: every field the edit modal sends is present after normalizing`, () => {
        const normalized = normalizeContractForEdit(contract, { id: 9, name: 'Hamlet' });

        for (const field of CONTRACT_EDIT_FIELDS) {
            assert.notEqual(normalized[field], undefined, `${field} fehlt`);
        }
        assert.equal(normalized.partner, 'Agentur XYZ');
        assert.equal(normalized.project.id, 9);
        assert.equal(normalized.project.name, 'Hamlet');
        assert.equal(normalized.foreign_tax, true);
        assert.equal(normalized.foreign_tax_city, 'Wien');
        assert.equal(normalized.contract_state, 'signed');
        assert.equal(normalized.amount, 0);
        assert.deepEqual(normalized.accessibleUsers.map((user) => user.id), [5]);
        assert.deepEqual(normalized.accessibleDepartments.map((department) => department.id), [6]);
    });
}

test('normalizing twice changes nothing', () => {
    const once = normalizeContractForEdit(rawModel, { id: 9, name: 'Hamlet' });

    assert.deepEqual(normalizeContractForEdit(once), once);
});

test('a contract without project stays without project', () => {
    assert.equal(normalizeContractForEdit({ ...rawModel, project_id: null }).project, null);
    assert.deepEqual(normalizeContractForEdit({ ...rawModel }, { id: 1, name: 'Anderes' }).project, { id: 9 });
    assert.equal(normalizeContractForEdit(null), null);
});

test('deadline keeps its calendar day in Europe/Berlin', () => {
    // So kam eine date-Spalte (06.10.) früher an: lokale Mitternacht als UTC-Zeitpunkt des Vortags
    const serializedMidnight = '2026-10-05T22:00:00.000000Z';

    assert.equal(new Date(serializedMidnight).toISOString().split('T')[0], '2026-10-05');
    assert.equal(toDateInputValue(serializedMidnight), '2026-10-06');
    assert.equal(toDateInputValue('2026-10-06'), '2026-10-06');
    assert.equal(toDateInputValue(new Date(2026, 9, 6)), '2026-10-06');
    assert.equal(toDateInputValue(null), null);
    assert.equal(toDateInputValue('kein Datum'), null);
    assert.equal(normalizeContractForEdit({ ...rawModel, deadline_date: serializedMidnight }).deadline_date, '2026-10-06');
});

test('currency, legal form and contract type are resolved from their ids instead of falling back to a default', () => {
    const lookups = {
        currencies: [{ id: 1, name: '€' }, { id: 2, name: 'CHF' }],
        companyTypes: [{ id: 3, name: 'GmbH' }],
        contractTypes: [{ id: 4, name: 'Gastspielvertrag' }],
    };
    const withoutRelations = {
        ...rawModel,
        currency: undefined,
        company_type: undefined,
        contract_type: undefined,
        currency_id: 2,
        company_type_id: 3,
        contract_type_id: 4,
    };

    const normalized = normalizeContractForEdit(withoutRelations, null, lookups);
    assert.deepEqual(normalized.currency, { id: 2, name: 'CHF' });
    assert.deepEqual(normalized.company_type, { id: 3, name: 'GmbH' });
    assert.deepEqual(normalized.contract_type, { id: 4, name: 'Gastspielvertrag' });

    // ohne Auswahlliste bleibt wenigstens die Id erhalten – nie still Währung 1
    assert.deepEqual(normalizeContractForEdit(withoutRelations).currency, { id: 2 });
    // ohne Id und ohne Relation: nichts erfinden
    assert.equal(
        normalizeContractForEdit({ ...withoutRelations, currency_id: null }).currency,
        null
    );
    // eine mitgelieferte Relation hat Vorrang
    assert.deepEqual(normalizeContractForEdit(rawModel, null, lookups).currency, { id: 1, name: '€' });
});
