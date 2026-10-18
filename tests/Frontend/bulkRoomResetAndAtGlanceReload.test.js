import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');

const bodyOf = (source, name) => {
    const start = source.indexOf(`${name} = `);
    assert.notEqual(start, -1, name);
    const end = source.indexOf('\n};\n', start);
    return source.slice(start, end === -1 ? undefined : end);
};

const bulkSingleEvent = read('Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue');

// 403 beim Raumwechsel (kein Buchungsrecht im Zielraum): die Zeile muss auf den gespeicherten Raum
// zurückspringen, sonst scheitert jede weitere Änderung erneut am mitgeschickten Raum.
test('bulk row resets the room after a forbidden room change only', () => {
    const body = bodyOf(bulkSingleEvent, 'const updateEventInDatabase');
    const catchBlock = body.slice(body.indexOf('catch (err)'));

    assert.match(body, /const requestChangedRoom = lastComparable && lastComparable\.roomId !== currentComparable\.roomId/);
    assert.match(catchBlock, /status === 403 && requestChangedRoom/);
    assert.match(catchBlock, /resetRoomToSavedSnapshot\(event, snapshotKey\)/);
});

test('room reset uses the current snapshot and a named placeholder', () => {
    const body = bodyOf(bulkSingleEvent, 'const resetRoomToSavedSnapshot');
    // aktueller Snapshot (nicht der vor dem PATCH gelesene), nur bei Abweichung vom angezeigten Raum
    assert.match(body, /window\.__bulkEventSnapshots\?\.\[snapshotKey\]/);
    assert.match(body, /savedComparable\.roomId === \(event\.room\?\.id \?\? null\)/);
    // Platzhalter für Räume außerhalb der Auswahl trägt einen Namen (sonst "[object Object]" in der Listbox)
    assert.match(body, /\{ id: savedComparable\.roomId, name: savedComparable\.roomName \?\? '' \}/);
});

test('failed room change does not overwrite a later saved room', () => {
    const body = bodyOf(bulkSingleEvent, 'const onRoomChange');
    const catchBlock = body.slice(body.indexOf('.catch('));
    assert.match(catchBlock, /resetRoomToSavedSnapshot\(event, /);
    // previousRoom nur noch ohne Snapshot und nur, solange noch der gescheiterte Raum angezeigt wird
    assert.match(catchBlock, /else if \(event\.room\?\.id === newRoom\?\.id\) \{\s*event\.room = previousRoom;/);
});

test('snapshot is created for every displayed event, also for recycled rows', () => {
    assert.match(
        bulkSingleEvent,
        /watch\(\(\) => props\.event\.id, \(\) => \{[\s\S]*?__bulkEventSnapshots\[`event-snapshot-\$\{event\.id\}`\] = getComparableEvent\(event\);[\s\S]*?\}, \{immediate: true\}\);/
    );
});

test('bulk snapshot keeps the room name for the reset placeholder', () => {
    const body = bodyOf(bulkSingleEvent, 'const getComparableEvent');
    assert.match(body, /roomName: ev\.room\?\.name \?\? null/);
});

const atGlance = read('Layouts/Components/IndividualCalendarAtGlanceComponent.vue');

// Im Projekt-Kalendertab hält CalendarTab die Daten lokal – router.reload() erneuert sie nicht.
for (const handler of ['onEventComponentClose', 'onEventsWithoutRoomComponentClose']) {
    test(`${handler} reloads the calendar tab data`, () => {
        const start = atGlance.indexOf(`    ${handler} = `);
        assert.notEqual(start, -1, handler);
        const end = atGlance.indexOf('\n    },', start);
        const body = atGlance.slice(start, end);

        assert.match(body, /reloadEvents\(\)/);
        assert.doesNotMatch(body, /router\.reload\(/);
    });
}
