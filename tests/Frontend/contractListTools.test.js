import test from 'node:test';
import assert from 'node:assert/strict';
import {
    contractSortAccessors,
    matchesContractSearch,
    todayDateString,
} from '../../resources/js/Pages/Contracts/contractListTools.js';

const contract = {
    partner: 'Kompanie Blau',
    project: {name: 'Open Air Saison'},
    name: 'gastspielvertrag.pdf',
    contract_type: {name: 'Gastspiel'},
    company_type: {name: 'GbR'},
    description: 'Tournee Herbst',
    amount: '1200.50',
    deadline_date: '2026-12-01',
};

test('search covers partner, project, file, type, legal form and description', () => {
    assert.equal(matchesContractSearch(contract, ''), true);
    for (const term of ['BLAU', 'open air', 'vertrag.pdf', 'gastspiel', 'gbr', 'tournee']) {
        assert.equal(matchesContractSearch(contract, term), true, term);
    }
    assert.equal(matchesContractSearch(contract, 'werkvertrag'), false);
    assert.equal(matchesContractSearch({}, 'x'), false);
});

test('sort accessors normalise text, amount and deadline', () => {
    assert.equal(contractSortAccessors.partner(contract), 'kompanie blau');
    assert.equal(contractSortAccessors.project(contract), 'open air saison');
    assert.equal(contractSortAccessors.contractType(contract), 'gastspiel');
    assert.equal(contractSortAccessors.filename(contract), 'gastspielvertrag.pdf');
    assert.equal(contractSortAccessors.amount(contract), 1200.5);
    assert.equal(contractSortAccessors.amount({amount: 0}), 0);
    assert.equal(contractSortAccessors.amount({amount: null}), null);
    assert.equal(contractSortAccessors.amount({amount: 'abc'}), null);
    assert.equal(contractSortAccessors.deadline(contract), '2026-12-01');
    assert.equal(contractSortAccessors.deadline({deadline_date: '2026-12-01T00:00:00Z'}), '2026-12-01');
    assert.equal(contractSortAccessors.deadline({}), null);
    assert.equal(contractSortAccessors.partner({partner: '  '}), null);
});

test('today is formatted as a local calendar date', () => {
    assert.equal(todayDateString(new Date(2026, 0, 5, 23, 59)), '2026-01-05');
    assert.equal(todayDateString(new Date(2026, 10, 19)), '2026-11-19');
});

test('deadline quick filters use calendar boundaries', async () => {
    const {matchesDeadlineFilter} = await import('../../resources/js/Pages/Contracts/contractListTools.js');
    // Donnerstag, 19.11.2026: Woche endet am Sonntag, 22.11., Monat am 30.11.
    const today = new Date(2026, 10, 19, 14, 0);
    const on = date => ({deadline_date: date});

    assert.equal(matchesDeadlineFilter(on('2026-11-18'), 'overdue', today), true);
    assert.equal(matchesDeadlineFilter(on('2026-11-19'), 'overdue', today), false);

    assert.equal(matchesDeadlineFilter(on('2026-11-19'), 'week', today), true);
    assert.equal(matchesDeadlineFilter(on('2026-11-22'), 'week', today), true);
    assert.equal(matchesDeadlineFilter(on('2026-11-23'), 'week', today), false);
    assert.equal(matchesDeadlineFilter(on('2026-11-18'), 'week', today), false);

    assert.equal(matchesDeadlineFilter(on('2026-11-30'), 'month', today), true);
    assert.equal(matchesDeadlineFilter(on('2026-12-01'), 'month', today), false);

    // an einem Sonntag endet die Woche am selben Tag
    assert.equal(matchesDeadlineFilter(on('2026-11-23'), 'week', new Date(2026, 10, 22)), false);
    assert.equal(matchesDeadlineFilter(on('2026-11-22'), 'week', new Date(2026, 10, 22)), true);

    assert.equal(matchesDeadlineFilter({}, 'overdue', today), false);
    assert.equal(matchesDeadlineFilter({}, null, today), true);
});

test('distinct states ignore case, blanks and duplicates', async () => {
    const {distinctContractStates, contractSortAccessors: accessors, matchesContractSearch: matches} =
        await import('../../resources/js/Pages/Contracts/contractListTools.js');
    assert.deepEqual(
        distinctContractStates([
            {contract_state: 'Unterschrieben'}, {contract_state: ' verschickt '}, {contract_state: 'unterschrieben'},
            {contract_state: ''}, {contract_state: null},
        ]),
        ['Unterschrieben', 'verschickt']
    );
    assert.equal(accessors.status({contract_state: 'Verschickt'}), 'verschickt');
    assert.equal(accessors.openTasks({open_tasks_count: 3}), 3);
    assert.equal(accessors.openTasks({}), 0);
    assert.equal(matches({contract_state: 'Unterschrieben'}, 'unterschr'), true);
});
