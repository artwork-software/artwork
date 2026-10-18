import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { register } from 'node:module';
import test from 'node:test';

// Die Off-by-one-Fehler treten nur östlich von UTC auf – so laufen sie wie bei den Nutzer*innen
process.env.TZ = 'Europe/Berlin';

register('./support/viteAliasHooks.mjs', import.meta.url);

const { parseYmd, toYmd } = await import('../../resources/js/Helper/IsoWeek.js');
const { useEvent } = await import('../../resources/js/Composeables/Event.js');

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('local midnight keeps its calendar day (toISOString would give the day before)', () => {
    const localMidnight = new Date(2026, 9, 5);

    assert.equal(localMidnight.toISOString().slice(0, 10), '2026-10-04');
    assert.equal(toYmd(localMidnight), '2026-10-05');
    // kurz nach Mitternacht liefert auch "heute" per toISOString noch den Vortag
    assert.equal(toYmd(new Date(2026, 0, 1, 0, 30)), '2026-01-01');
});

test('parseYmd reads dates locally and survives the DST switch', () => {
    const start = parseYmd('2026-03-29');
    start.setDate(start.getDate() + 1);
    assert.equal(toYmd(start), '2026-03-30');

    assert.equal(toYmd(parseYmd('2026-10-25T00:00:00.000000Z')), '2026-10-25');
    assert.equal(parseYmd(''), null);
    assert.equal(parseYmd(null), null);
    assert.equal(parseYmd('31.12.2026'), null);
});

test('getDaysOfEvent lists every day once across the DST switch', () => {
    const { getDaysOfEvent } = useEvent();

    assert.deepEqual(getDaysOfEvent('2026-03-28', '2026-03-31'), ['28.03.2026', '29.03.2026', '30.03.2026', '31.03.2026']);
    assert.deepEqual(getDaysOfEvent('2026-10-24', '2026-10-26'), ['24.10.2026', '25.10.2026', '26.10.2026']);
    assert.deepEqual(getDaysOfEvent('2026-10-05', '2026-10-05'), ['05.10.2026']);
});

test('shift plan date shortcuts no longer format local dates via toISOString', () => {
    for (const file of [
        'resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue',
        'resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanListViewFunctionBar.vue',
        'resources/js/Pages/Shifts/ShiftPlanDailyView.vue',
        'resources/js/Pages/Shifts/ShiftPlanListView.vue',
        'resources/js/Pages/Projects/Components/BulkComponents/BulkBody.vue',
        'resources/js/Components/FunctionBars/FunctionBarCalendar.vue',
        'resources/js/Layouts/Components/IndividualCalendarComponent.vue',
        'resources/js/Pages/Projects/Components/AddShiftModal.vue',
    ]) {
        const source = read(file);
        const localIsoSlices = source.match(/(?:currentWeek(?:Start|End)|monday|sunday|range\.(?:start|end)|newDate|startDate|new Date\(\))\.toISOString\(\)/g);
        assert.equal(localIsoSlices, null, file);
    }
});

test('YYYY-MM-DD values are parsed locally before local day arithmetic', () => {
    const files = {
        'resources/js/Layouts/Components/IndividualCalendarComponent.vue': 2,
        'resources/js/Pages/Projects/Components/AddShiftModal.vue': 1,
        'resources/js/Pages/Projects/Components/BulkComponents/BulkBody.vue': 4,
        'resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanListViewFunctionBar.vue': 2,
        'resources/js/Pages/Shifts/ShiftPlanListView.vue': 1,
    };
    for (const [file, count] of Object.entries(files)) {
        assert.equal((read(file).match(/parseYmd\(/g) ?? []).length, count, file);
    }
});
