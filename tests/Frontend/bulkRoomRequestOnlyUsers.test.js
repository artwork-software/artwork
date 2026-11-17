import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');

const bulkBody = read('Pages/Projects/Components/BulkComponents/BulkBody.vue');
const bulkSingleEvent = read('Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue');

// Wer nur "Raumbelegungen anfragen" hat, legt in der Terminliste Raumanfragen an (Backend:
// authorizeBulkEventCreationOrRoomRequest) und bearbeitet die eigenen Termine (EventPolicy::update).
test('request-only users may create events in the bulk list', () => {
    assert.match(bulkBody, /const canOnlyRequestRooms = !canCreateEventsDirectly && can\('request room occupancy'\);/);
    assert.match(bulkBody, /const hasCreateEventsPermission = ref\(canCreateEventsDirectly \|\| canOnlyRequestRooms\);/);
});

test('request-only users edit only their own rows', () => {
    assert.match(
        bulkBody,
        /canOnlyRequestRooms && \(!event\?\.id \|\| event\.created_by_id === usePage\(\)\.props\.auth\.user\.id\)/,
    );
    assert.match(bulkBody, /:can-edit-component="canEditComponent && canEditEventRow\(item\.event\)"/);
    assert.match(bulkBody, /:has-permission="canEditEventRow\(item\.event\)"/);
});

test('rows know who created them and whether they are a room request', () => {
    // Erstladen (Minimal-Resource) und Antwort/Broadcast (BulkEventChanged::eventPayload)
    assert.match(bulkBody, /created_by_id: event\.created_by\?\.id \?\? event\.user_id \?\? null,/);
    assert.match(bulkBody, /created_by_id: payload\.created_by_id \?\? null,/);
    assert.match(bulkBody, /occupancy_option: !!event\.occupancy_option,/);
    assert.match(bulkBody, /occupancy_option: !!payload\.occupancy_option,/);
});

test('request-only users cannot create planned events', () => {
    // Geplante Termine kennen keine Raumanfrage: Schalter weg, Vorbelegung aus
    assert.match(bulkBody, /if \(canOnlyRequestRooms\) return false;/);
    assert.match(bulkBody, /v-if="hasCreateEventsPermission && canEditComponent && !canOnlyRequestRooms"/);
});

test('rows without edit right keep the width of the actions column', () => {
    assert.match(bulkBody, /:reserve-actions-space="canEditComponent && hasCreateEventsPermission"/);
    assert.match(bulkSingleEvent, /v-else-if="reserveActionsSpace"/);
});

test('a saved row takes over the room request flag from the response', () => {
    assert.match(bulkSingleEvent, /event\.occupancy_option = !!savedEvent\.occupancy_option;/);
    assert.match(bulkSingleEvent, /v-if="event\.occupancy_option && !event\.is_planning"/);
});
