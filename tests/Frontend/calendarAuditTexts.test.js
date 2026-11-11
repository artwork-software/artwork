import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

const de = JSON.parse(readFileSync(new URL('../../lang/de.json', import.meta.url), 'utf8'));
const en = JSON.parse(readFileSync(new URL('../../lang/en.json', import.meta.url), 'utf8'));
const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const assertKeyUsed = (source, key, file) => {
    assert.ok(source.includes(`'${key}'`), `${file} misses: ${key}`);
    assert.equal(en[key], key, `en.json: ${key}`);
    assert.ok(de[key], `de.json: ${key}`);
};

const assertKeyRemoved = (source, key, file) => {
    assert.ok(!source.includes(key), `${file} still uses: ${key}`);
    assert.equal(de[key], undefined, `de.json still has: ${key}`);
    assert.equal(en[key], undefined, `en.json still has: ${key}`);
};

test('calendar settings describe start and end time as the hidden range of the day view', () => {
    const file = 'resources/js/Pages/Settings/Calendar/Index.vue';
    const source = read(file);

    // HandleInertiaRequests baut calendarHours aus Start/Ende, die Tagesansichten blenden genau diesen Bereich aus
    for (const key of [
        'Start and end time define which hours the day view of the calendar and the shift plan displays. The range may span midnight — for example 22:00 to 08:00.',
        'Defines which hours the day view of the calendar and the shift plan displays. The range may span midnight (e.g. 22:00–08:00).',
    ]) {
        assertKeyRemoved(source, key, file);
    }

    for (const key of [
        'Start and end time define a range of hours that the day view of the calendar and the shift plan hides, as long as nothing is scheduled in it on that day. The range may span midnight: to keep 08:00 to 23:00 visible, enter 23:00 as the start and 08:00 as the end.',
        'Defines which hours the day view of the calendar and the shift plan hides. Hours with events or shifts stay visible. The range may span midnight (e.g. hidden from 22:00 until 08:00).',
        'Hidden from',
        'Hidden until',
    ]) {
        assertKeyUsed(source, key, file);
    }

    assert.ok(!source.includes("$t('Start-Time')"), 'start field still labelled as start time');
    assert.ok(!source.includes("$t('End-Time')"), 'end field still labelled as end time');
});

test('adjoining rooms are described as information, not as part of the conflict check', () => {
    const file = 'resources/js/Pages/Areas/AreaManagement.vue';
    const source = read(file);

    // EventCollisionService prüft nur denselben Raum; die Meldung zu Nebenräumen löst kein Formular mehr aus
    assertKeyRemoved(
        source,
        'Adjoining rooms link rooms for the conflict check: a room counts as occupied if an adjoining room hosts a loud event or an event with audience in the same period.',
        file,
    );
    assertKeyUsed(
        source,
        'Adjoining rooms record which rooms are next to each other. This is information about the room only: the conflict check still looks at the room itself, and events in an adjoining room neither block it nor trigger a warning.',
        file,
    );
});

test('monthly overview export marks the preselected paper size as the default', () => {
    const source = read('resources/js/Layouts/Components/Export/Tabs/PdfMonthlyCalendarExport.vue');

    assert.ok(source.includes("{ id: 'a3', name: 'A3 (Standard)' }"), 'A3 is not marked as default');
    assert.ok(!source.includes("'A4 (Standard)'"), 'A4 is still marked as default');
    assert.match(source, /selectedPaperSize = ref<[^>]+>\(\{ id: 'a3', name: 'A3 \(Standard\)' \}\)/);
});

test('event delete dialogs point to the trash instead of calling the deletion irreversible', () => {
    // Termine werden weich gelöscht (SoftDeletes) und lassen sich aus dem Papierkorb wiederherstellen
    const bulkFile = 'resources/js/Pages/Projects/Components/BulkComponents/BulkBody.vue';
    const bulk = read(bulkFile);
    assert.ok(!bulk.includes("$t('This action cannot be undone.')"), `${bulkFile} still calls the deletion irreversible`);
    assertKeyUsed(bulk, 'The events are moved to the trash and can be restored from there within 30 days.', bulkFile);

    const dailyFile = 'resources/js/Pages/Shifts/DailyViewComponents/SingleEventInDailyShiftView.vue';
    const daily = read(dailyFile);
    assertKeyRemoved(daily, 'Do you really want to delete this event? This action cannot be undone.', dailyFile);
    assertKeyUsed(
        daily,
        'Do you really want to delete this event? It is moved to the trash and can be restored from there within 30 days.',
        dailyFile,
    );
});
