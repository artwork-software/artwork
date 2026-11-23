import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');

const bulkSingleEvent = read('Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue');
const answerModal = read('Pages/Projects/Components/BulkComponents/BulkRoomRequestAnswerModal.vue');
const broadcastUpdater = read('Composeables/Listener/useBulkEventsBroadcastUpdater.js');

// Die Terminliste zeigt keine Timelines: Vorlagen speichern/importieren gehört nicht ins Zeilenmenü.
test('the bulk row menu offers no timeline presets', () => {
    assert.doesNotMatch(bulkSingleEvent, /timeline/i);
});

test('room requests are answered from the row: icon, menu and dialog', () => {
    // Recht wie EventPolicy::answerRoomRequest, unabhängig vom Bearbeitungsrecht der Zeile
    assert.match(bulkSingleEvent, /const canAnswerRoomRequest = computed\(\(\) => !!props\.event\.id\s+&& !!props\.event\.occupancy_option\s+&& !props\.event\.is_planning/);
    assert.match(bulkSingleEvent, /mayAnswerRoomRequestIn\(\{/);
    assert.match(bulkSingleEvent, /<button\s+v-if="canAnswerRoomRequest"[\s\S]*?@click\.stop="showAnswerRoomRequestModal = true"/);
    assert.match(bulkSingleEvent, /title="Confirm event"\s+@click="answerRoomRequest\(\{accepted: true\}\)"/);
    assert.match(bulkSingleEvent, /title="Decline event"\s+@click="showAnswerRoomRequestModal = true"/);
    assert.match(answerModal, /\$emit\('answer', \{accepted: false, comment\}\)/);
    assert.match(answerModal, /\$emit\('answer', \{accepted: true, comment\}\)/);
});

test('the answer goes to the existing endpoints and the row takes over the result', () => {
    assert.match(bulkSingleEvent, /axios\.put\(route\('events\.accept', \{event: event\.id\}\), \{adminComment: comment\}\)/);
    assert.match(bulkSingleEvent, /axios\.put\(route\('events\.decline', \{event: event\.id\}\), \{comment\}\)/);
    assert.match(bulkSingleEvent, /event\.occupancy_option = !!answeredEvent\.occupancy_option;/);
    // Absage: der Termin hat keinen Raum mehr
    assert.match(bulkSingleEvent, /event\.room = answeredEvent\.room\s+\? \(roomList\.find\(room => room\.id === answeredEvent\.room\.id\) \?\? answeredEvent\.room\)\s+: null;/);
});

test('other open lists drop the room of a declined request', () => {
    assert.match(broadcastUpdater, /const requestWasDeclined = existing\.occupancy_option && event\.occupancy_option === false;/);
    assert.match(broadcastUpdater, /if \(k === 'room' && event\[k\] === null && !requestWasDeclined\) \{/);
});
