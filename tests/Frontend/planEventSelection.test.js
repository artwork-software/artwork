import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import {
    createPlanEventSelection,
    isPlanEventSelected,
    selectedPlanEventIds,
    togglePlanEvent,
    togglePlanEventType,
} from '../../resources/js/Helper/planEventSelection.js';

const TRANSFER = 1;
const REHEARSAL = 2;
const events = [
    {id: 10, event_type_id: TRANSFER},
    {id: 11, event_type_id: REHEARSAL},
    {id: 12, event_type_id: TRANSFER},
];

test('remembered event types preselect their events', () => {
    const selection = createPlanEventSelection([TRANSFER]);

    assert.deepEqual(selectedPlanEventIds(selection, events), [10, 12]);
});

test('single events can be deselected and added against the type preselection', () => {
    let selection = createPlanEventSelection([TRANSFER]);
    selection = togglePlanEvent(selection, events[0]);
    selection = togglePlanEvent(selection, events[1]);

    assert.deepEqual(selectedPlanEventIds(selection, events), [11, 12]);

    // erneutes Umschalten stellt die Vorauswahl wieder her, ohne Reste zu hinterlassen
    selection = togglePlanEvent(selection, events[0]);
    selection = togglePlanEvent(selection, events[1]);
    assert.deepEqual(selectedPlanEventIds(selection, events), [10, 12]);
    assert.equal(selection.included.size, 0);
    assert.equal(selection.excluded.size, 0);
});

test('toggling a type resets the single choices of its events only', () => {
    let selection = createPlanEventSelection([TRANSFER]);
    selection = togglePlanEvent(selection, events[0]);
    selection = togglePlanEvent(selection, events[1]);

    selection = togglePlanEventType(selection, TRANSFER, events);
    assert.deepEqual(selectedPlanEventIds(selection, events), [11]);
    assert.equal(isPlanEventSelected(selection, events[0]), false);

    selection = togglePlanEventType(selection, TRANSFER, events);
    assert.deepEqual(selectedPlanEventIds(selection, events), [10, 11, 12]);
});

test('selection changes never mutate the previous state', () => {
    const before = createPlanEventSelection([TRANSFER]);
    togglePlanEvent(before, events[0]);
    togglePlanEventType(before, REHEARSAL, events);

    assert.deepEqual([...before.typeIds], [TRANSFER]);
    assert.equal(before.excluded.size, 0);
});

test('project header opens the plan export dialog', () => {
    const header = readFileSync(
        new URL('../../resources/js/Pages/Projects/Tab/Components/ProjectHeaderComponent.vue', import.meta.url),
        'utf8'
    );

    assert.match(header, /<ProjectPlanExportModal\b/);
    assert.match(header, /icon="IconFileTypeDocx"/);
});
